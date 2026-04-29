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
- **Cosa fa:** partendo dal task modificato, risale alla lavorazione (ProjectMilestone) della
  stessa coppia `Type + Zona Lavorazione`, aggiorna i parziali della giornata, riaggrega tutte
  le giornate della stessa coppia, aggiorna la lavorazione, poi aggiorna il cantiere.
- **Passi SQL:**
  1. Legge dal task modificato: `projectid`, `projecttasktype`, `cf_918`,
     `cf_887`, `cf_893`, `cf_877`
  2. Legge dalla lavorazione corrispondente: `cf_875`, `cf_885`, `cf_904`,
     `projectmilestonedate`, `projectmilestonedeliverydate`, propagando nello stesso `$_row`
     anche `numero_operai`, `spese`, `quantita_eseguita`
  3. UPDATE della giornata su `vtiger_projecttaskcf`:
     `cf_922 = spese + (numero_operai * costo_operaio)`
     `cf_920 = quantita_eseguita * prezzo_unitario`
  4. Aggrega le giornate della stessa coppia `Type + Zona Lavorazione`
  5. UPDATE su `vtiger_projectmilestonecf` (cf_867, cf_897, cf_906, cf_908, cf_910, cf_912, **cf_914**)
  6. Somma totali di tutte le lavorazioni del progetto
  7. UPDATE su `vtiger_projectcf` (cf_879, cf_881, cf_883)

### Workflow 36 — trigger su `ProjectMilestone`
- **task_id DB:** `40` in `com_vtiger_workflowtasks`
- **Cosa fa:** partendo dalla lavorazione modificata, ricalcola le sue giornate, aggiorna
  la lavorazione stessa, poi aggiorna il cantiere.
- **Passi SQL:**
  1. Legge dalla lavorazione modificata (`$_id`): `costo_operaio`, `prezzo_unitario`,
     `totale_da_lavorare`, `projectmilestonedate`, `projectmilestonedeliverydate`, `cf_916`
  2. UPDATE di tutte le giornate della stessa coppia `Type + Zona Lavorazione`:
     `cf_922 = spese + (numero_operai * costo_operaio)`
     `cf_920 = quantita_eseguita * prezzo_unitario`
  3. Aggrega le giornate della stessa coppia `Type + Zona Lavorazione`
  4. UPDATE su `vtiger_projectmilestonecf` (cf_867, cf_897, cf_906, cf_908, cf_910, cf_912, **cf_914**)
  5. Somma totali di tutte le lavorazioni del progetto
  6. UPDATE su `vtiger_projectcf` (cf_879, cf_881, cf_883)

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
| cf_916  | Zona Lavorazione |

### `vtiger_projecttaskcf`
| Colonna | Label |
|---------|-------|
| cf_877  | Quantità Eseguita |
| cf_887  | Numero Operai |
| cf_893  | Spese |
| cf_918  | Zona Lavorazione |
| cf_920  | Totale Ricavo |
| cf_922  | Totale Costo |

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
- La logica attuale su `medipav` usa la coppia `Type + Zona Lavorazione` sia per:
  - conteggio giornate della lavorazione (`cf_910`)
  - avanzamento percentuale (`cf_912`)
  - avanzamento giornate percentuale (`cf_914`)
  - totali costi/ricavi della lavorazione (`cf_906`, `cf_908`)
- I parziali delle giornate non sono più dati statici: vengono rigenerati dai workflow:
  - `ProjectTask.cf_922 = Spese + (Numero Operai * Costo Operaio lavorazione)`
  - `ProjectTask.cf_920 = Quantità Eseguita * Prezzo Unitario lavorazione`
- Caso validato su live:
  - `ProjectTask 379` (`30x379`) collegata a `ProjectMilestone 377` (`29x377`)
  - con `cf_887 = 4`, `cf_893 = 70`, `cf_877 = 600`, `cf_875 = 310`, `cf_885 = 5`
  - i parziali corretti sono `cf_922 = 1310` e `cf_920 = 3000`
  - i totali lavorazione corretti sono `cf_906 = 25799.85000` e `cf_908 = 37500.00000`
- La serializzazione PHP nei workflow tasks è valida (verificata con controllo lunghezze `s:N:`).

### Cosa NON funziona / da verificare
- `vtc revise` **su `ProjectMilestone`** scatta il workflow ed è affidabile per riallineare
  giornata/lavorazione/cantiere.
- `vtc revise` **su `ProjectTask`** scatta il workflow, ma è il punto dove si è visto il bug
  di propagazione tra step 1 e step 3. Dopo la correzione, il dump SQL mostra il passaggio
  corretto di `numero_operai`, `spese`, `quantita_eseguita`.
- `cf_914` sulle milestone che non sono mai state salvate dopo l'aggiornamento del workflow
  può restare `NULL` finché non passa almeno un salvataggio utile.

### Bug trovato e risolto
Nel workflow `33`, prima della fix:
1. `Statement 1` leggeva correttamente `numero_operai`, `spese`, `quantita_eseguita`
2. `Statement 2` sovrascriveva `$_row` senza riportare quei tre valori
3. `Statement 3` vedeva le chiavi mancanti e il motore le sostituiva con `0`
4. Risultato: `cf_920 = 0`, `cf_922 = 0`, e i totali della lavorazione/cantiere scendevano

Fix applicata:
1. Nel `Statement 2` sono stati propagati:
   - `numero_operai`
   - `spese`
   - `quantita_eseguita`
2. Il dump successivo ha mostrato:
   - `cf_922 = 70 + (4 * 310) = 1310`
   - `cf_920 = 600 * 5 = 3000`
3. Gli UPDATE finali su lavorazione e cantiere sono tornati corretti (`Righe interessate: 1`)

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
```

### Strumenti remoti usati davvero in questa sessione
```bash
# Lettura DB live
CRM=medipav vtc inspect db tables
CRM=medipav vtc inspect db columns vtiger_projecttaskcf
CRM=medipav vtc inspect db query "SELECT ..."

# Scrittura diretta DB live
CRM=medipav vtc control db execute "UPDATE ..."

# Salvataggio record via webservice
CRM=medipav vtc revise ProjectMilestone '{"id":"29x377","cf_875":"310.00000"}'
CRM=medipav vtc revise ProjectTask '{"id":"30x379","cf_893":"70.00000"}'
```

### Come ripartire da qui
1. Per ispezionare i workflow live:
   `CRM=medipav vtc inspect db query "SELECT task_id, summary, task FROM com_vtiger_workflowtasks WHERE workflow_id IN (33,36)"`
2. Per verificare i parziali di una giornata:
   `CRM=medipav vtc inspect db query "SELECT cf_877, cf_887, cf_893, cf_920, cf_922 FROM vtiger_projecttaskcf WHERE projecttaskid = ..."`
3. Per verificare i totali di una lavorazione:
   `CRM=medipav vtc inspect db query "SELECT cf_906, cf_908, cf_910, cf_912, cf_914 FROM vtiger_projectmilestonecf WHERE projectmilestoneid = ..."`
4. Per verificare i totali cantiere:
   `CRM=medipav vtc inspect db query "SELECT cf_879, cf_881, cf_883 FROM vtiger_projectcf WHERE projectid = ..."`
5. Se una giornata resta incoerente, il modo più sicuro per riallineare tutto è salvare la
   `ProjectMilestone` collegata via UI o via:
   `CRM=medipav vtc revise ProjectMilestone '{"id":"29x...","cf_875":"valore_attuale"}'`
6. Se serve aggiornare di nuovo le SQL dei workflow dal repo locale:
   `python3 contrib/update_workflow_totals.py --apply`

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
- `contrib/update_workflow_totals.py` — helper locale per rigenerare e applicare le SQL dei workflow 33/36
- Workflow DB task_id=38 (wf 33) e task_id=40 (wf 36) — aggiornati via UNHEX su medipav
