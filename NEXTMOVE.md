# NEXTMOVE — Debug: Avanzamento Giornate Percentuale (cf_914)

## Obiettivo della feature
Campo `cf_914` ("Avanzamento Giornate Percentuale") su `ProjectMilestone`.

**Formula:**
```
cf_914 = (COUNT giornate caricate / (DATEDIFF(data_consegna, data_inizio) + 1)) * 100
```
Il `+1` serve perché una milestone di un solo giorno ha durata 1, non 0.

**Trigger:**
- Quando si salva un `ProjectTask` (giornata) → aggiorna la lavorazione corrispondente
- Quando si salvano le date di un `ProjectMilestone` (lavorazione) → ricalcola direttamente

---

## Architettura dei Workflow

Ci sono due workflow `VTDatabaseQueryTask` che eseguono query SQL a cascata:

### Workflow 33 — trigger su `ProjectTask`
- **task_id DB:** `38` in `com_vtiger_workflowtasks`
- **Cosa fa:** partendo dal task modificato, risale alla lavorazione (ProjectMilestone) del
  suo tipo, riaggrega tutte le giornate, aggiorna la lavorazione, poi aggiorna il cantiere.
- **Passi SQL:**
  1. Legge `projectid` e `projecttasktype` del task modificato (`$_id`)
  2. Legge dalla lavorazione corrispondente: `costo_operaio`, `prezzo_unitario`,
     `totale_da_lavorare`, `projectmilestonedate`, `projectmilestonedeliverydate`
  3. Aggrega le giornate dello stesso tipo → calcola `avanzamento_giornate_percentuale` (cf_914)
  4. UPDATE su `vtiger_projectmilestonecf` (cf_867, cf_897, cf_906, cf_908, cf_910, cf_912, **cf_914**)
  5. Somma totali di tutte le lavorazioni del progetto
  6. UPDATE su `vtiger_projectcf` (cf_879, cf_881, cf_883)

### Workflow 36 — trigger su `ProjectMilestone`
- **task_id DB:** `40` in `com_vtiger_workflowtasks`
- **Cosa fa:** partendo dalla lavorazione modificata, ricalcola le sue giornate, aggiorna
  la lavorazione stessa, poi aggiorna il cantiere.
- **Passi SQL:**
  1. Legge dalla lavorazione modificata (`$_id`): `costo_operaio`, `prezzo_unitario`,
     `totale_da_lavorare`, `projectmilestonedate`, `projectmilestonedeliverydate`
  2. Aggrega le giornate dello stesso tipo → calcola `avanzamento_giornate_percentuale` (cf_914)
  3. UPDATE su `vtiger_projectmilestonecf` (cf_867, cf_897, cf_906, cf_908, cf_910, cf_912, **cf_914**)
  4. Somma totali di tutte le lavorazioni del progetto
  5. UPDATE su `vtiger_projectcf` (cf_879, cf_881, cf_883)

---

## Mappa campi rilevanti

### `vtiger_projectmilestonecf`
| Colonna | Label |
|---------|-------|
| cf_867  | Avanzamento |
| cf_875  | Costo Operaio |
| cf_885  | Prezzo Unitario |
| cf_897  | Totale Spese |
| cf_904  | Totale da Lavorare |
| cf_906  | Totale Costi |
| cf_908  | Totale Ricavi |
| cf_910  | Avanzamento Giornate (COUNT tasks) |
| cf_912  | Avanzamento Percentuale |
| **cf_914** | **Avanzamento Giornate Percentuale** ← il campo nuovo |

### `vtiger_projectmilestone`
| Colonna | Label |
|---------|-------|
| projectmilestonedate | Data inizio lavorazione |
| projectmilestonedeliverydate | Data consegna lavorazione |
| projectmilestonetype | Tipo (es. "Preparazione", "Getto") |

### `vtiger_projectcf`
| Colonna | Label |
|---------|-------|
| cf_879  | Totale Costi cantiere |
| cf_881  | Totale Ricavi cantiere |
| cf_883  | Margine (ricavi - costi) |

---

## Stato attuale del debug

### Cosa funziona
- Il workflow **gira correttamente dalla UI del CRM** (salvataggio manuale).
- La milestone `id=269` ha `cf_914 = 20.00%` — prova che il meccanismo funziona.
  (Quel valore è della vecchia formula senza `+1`; al prossimo salvataggio UI sarà corretto.)
- La serializzazione PHP nei workflow tasks è valida (verificata con controllo lunghezze `s:N:`).

### Cosa NON funziona / da verificare
- **`vtc revise` non scatta i workflow** — la webservice API bypassa il sistema di hook
  vtiger (`vtiger.entity.aftersave`). Usare `vtc revise` per testare non è valido;
  bisogna salvare dalla UI del CRM.
- `cf_914` sulle milestone che non sono mai state salvate dopo l'aggiornamento del workflow
  è ancora `NULL`. Si aggiorna al primo salvataggio dalla UI.

### Prova da fare per confermare il fix
1. Aprire una `ProjectMilestone` con `projectmilestonedate` e `projectmilestonedeliverydate`
   compilate nel CRM (es. milestone `id=263`: 2026-04-06 → 2026-04-12, 7 giorni).
2. Salvarla dalla UI senza modificare nulla.
3. Verificare che `cf_914` venga aggiornato a `(giornate / 7) * 100`.

---

## Come interagire col DB dell'istanza medipav

```bash
# Query in sola lettura
CRM=medipav vtc inspect db query "SELECT ..."

# Scrittura (singolo statement, no punto e virgola nel contenuto)
CRM=medipav vtc control db execute "UPDATE ..."

# Per UPDATE con contenuto lungo che contiene punti e virgola (es. task workflow):
# usare UNHEX() — vedere sezione "Aggiornare i workflow tasks" sotto
```

### Leggere i workflow tasks
```bash
CRM=medipav vtc inspect db query "SELECT task_id, summary, task FROM com_vtiger_workflowtasks WHERE workflow_id = 33"
# Il campo `task` è un oggetto PHP serializzato. Decodificarlo con html.unescape() in Python.
# La query SQL è dentro: s:5:"query";s:N:"...SQL...";
```

### Aggiornare le query dei workflow tasks (pattern UNHEX)
Il campo `task` contiene punti e virgola dentro le query → `control db execute` li rifiuta.
Usare `UNHEX()` per bypassare:

```python
import subprocess, json, re, html, os

def get_task(workflow_id):
    r = subprocess.run(['vtc','inspect','db','query',
        f"SELECT task_id, task FROM com_vtiger_workflowtasks WHERE workflow_id = {workflow_id}"],
        capture_output=True, text=True, env={**os.environ,'CRM':'medipav'})
    row = json.loads(r.stdout)['result']['rows'][0]
    return row['task_id'], html.unescape(row['task'])

def replace_query_in_serialized(raw, new_query):
    new_len = len(new_query)
    return re.sub(
        r's:5:"query";s:\d+:".*?";(s:\d+:"(?:field_value_mapping|id)")',
        lambda m: f's:5:"query";s:{new_len}:"{new_query}";{m.group(1)}',
        raw, flags=re.DOTALL
    )

def update_workflow_query(workflow_id, new_query):
    task_id, raw = get_task(workflow_id)
    new_serialized = replace_query_in_serialized(raw, new_query)
    hex_val = new_serialized.encode('utf-8').hex()
    sql = f"UPDATE com_vtiger_workflowtasks SET task = UNHEX('{hex_val}') WHERE task_id = {task_id}"
    r = subprocess.run(['vtc','control','db','execute', sql],
        capture_output=True, text=True, env={**os.environ,'CRM':'medipav'})
    return json.loads(r.stdout)
```

### Trovare i webservice ID per retrieve/revise
```bash
CRM=medipav vtc inspect db query "SELECT id, name FROM vtiger_ws_entity WHERE name = 'ProjectMilestone'"
# ProjectMilestone → id=29  →  crmid formato: "29x<projectmilestoneid>"
# ProjectTask      → id=?   →  verificare con stessa query
```

### Trigger manuale di un workflow (solo per test — non scatta i workflow reali)
```bash
CRM=medipav vtc revise ProjectMilestone '{"id":"29x269","projectmilestonedate":"2026-04-14"}'
# ATTENZIONE: vtc revise bypassa i workflow hook vtiger. Non usarlo per testare cf_914.
```

---

## Handler PHP del workflow
**File locale:** `lib/modules/com_vtiger_workflow/tasks/VTDatabaseQueryTask.inc`

Punti chiave del codice:
- `splitStatements()` → divide per `;`
- `replaceVariables()` → sostituisce `$_id`, `$_row[campo]`, `$nomecampo`
- `statementType()` → rileva SELECT/UPDATE/INSERT (stripping commenti `--`)
- Errori SQL sono **silenziosi** (`$adb->query($sql, false)`) — se un UPDATE fallisce, non c'è traccia

Per abilitare il dump di debug sul server:
```bash
# Decommentare la riga nel file remoto:
# file_put_contents(__FILE__ . '.dump', $dumpInfo, FILE_APPEND);
# poi leggere: vtc inspect fs read "lib/modules/com_vtiger_workflow/tasks/VTDatabaseQueryTask.inc.dump"
# NB: vtc inspect fs list "" mostra la root del progetto (non /var/www/html/)
```

---

## File modificati in questo branch
- `lib/libraries/jquery/gantt/ganttDrawerSVG.js` — fix workflow + nuovi metodi gantt
- `lib/libraries/jquery/gantt/gantt.css` — stili progress bars custom
- `lib/modules/Project/models/Record.php` — rimosso clamp >100 su normalizeGanttProgressValue
- `lib/pkg/vtiger/modules/Projects/Project/modules/Project/models/Record.php` — idem
- `lib/modules/com_vtiger_workflow/tasks/VTDatabaseQueryTask.inc` — handler workflow SQL
- Workflow DB task_id=38 (wf 33) e task_id=40 (wf 36) — aggiornati via UNHEX su medipav