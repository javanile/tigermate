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
| **cf_914** | **Avanzamento Giornate Percentuale** |
| cf_916  | Zona Lavorazione |
| cf_928  | Margine lavorazione (ricavi − costi) |
| cf_932  | Costo Assistente |

### `vtiger_projecttaskcf`
| Colonna | Label |
|---------|-------|
| cf_877  | Quantità Eseguita |
| cf_887  | Numero Operai |
| cf_893  | Spese |
| cf_918  | Zona Lavorazione |
| cf_920  | Totale Ricavo (giornata) |
| cf_922  | Totale Costo (giornata) |
| cf_926  | Margine giornata (ricavo − costo) |
| cf_930  | Mezza Giornata (flag '1') |
| cf_934  | Numero Assistenti |

### `vtiger_projectmilestone`
| Colonna | Label |
|---------|-------|
| projectmilestonedate | Data inizio lavorazione |
| projectmilestonedeliverydate | Data consegna lavorazione |
| projectmilestonetype | Tipo (es. "Preparazione", "Getto") |

### `vtiger_projectcf`
| Colonna | Label |
|---------|-------|
| cf_865  | Avanzamento |
| cf_879  | Totale Costi cantiere |
| cf_881  | Totale Ricavi cantiere |
| cf_883  | **MOL** (Margine Operativo Lordo = ricavi − costi) |
| cf_936  | Ricavo Extra |
| cf_938  | Costo Extra |
| cf_940  | **MOL %** (= cf_883 / cf_881 * 100) |
| cf_942  | Costo Materiali |

### `vtiger_salesorder`
| Colonna | Label |
|---------|-------|
| subject | Oggetto — **agganciato per nome a `vtiger_project.projectname`** |
| total   | Totale (grand total, IVA inclusa) |
| subtotal| Imponibile (pre-tax) |

> Aggancio cantiere ⇄ SalesOrder: **`vtiger_salesorder.subject = vtiger_project.projectname`** (match per nome, niente FK).

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

### File modificati il 2026-06-16 (sessione Materiali / MOL / Extra)
Codice UI (vanno deployati sull'istanza):
- `lib/modules/Project/models/DetailView.php` — tab "Materiali" in `getDetailViewRelatedLinks()` (flavor construction)
- `lib/pkg/vtiger/modules/Projects/Project/modules/Project/models/DetailView.php` — gemello sincronizzato
- `lib/modules/Project/views/Detail.php` — `exposeMethod('showMaterials')` + `$_REQUEST['materialsProjectId']`
- `lib/pkg/vtiger/modules/Projects/Project/modules/Project/views/Detail.php` — gemello sincronizzato
- `lib/layouts/v7/modules/Inventory/LineItemsDetail.tpl` — bottone "Aggiungi Materiali" (solo in tab Materiali)
- `lib/layouts/v7/modules/Project/resources/Detail.js` — al load riapre la tab Materiali
- `lib/pkg/vtiger/modules/Projects/Project/layouts/v7/modules/Project/resources/Detail.js` — gemello sincronizzato
- `lib/layouts/v7/modules/Vtiger/uitypes/StringDetailView.tpl` — mostra "%" sui campi percentuale (Summary/Detail)
- `lib/modules/Settings/Workflows/models/Record.php` — `getDependentModules()`: SalesOrder selezionabile in "Crea Record" (flavor construction)

Script helper `contrib/` (dry-run default, `--apply` per scrivere; pattern UNHEX + validazione PHP):
- `contrib/update_wf43_materiali.py` — wf43 v1 (solo `cf_942`)
- `contrib/update_wf43_corrective.py` — wf43 correttivo (cf_942 + ricalcolo totali/MOL/MOL%, fix lavorazioni deleted)
- `contrib/update_wf36_extra.py` / `contrib/update_wf33_extra.py` — Ricavo/Costo Extra + Materiali nei totali cantiere
- `contrib/add_mol_pct.py` — aggiunge `cf_940` (MOL%) all'UPDATE finale di wf33 (task 38) e wf36 (task 40)

Workflow DB modificati via UNHEX su medipav:
- task_id=49 (wf 43, SalesOrder) — query custom: scrive `cf_942` + ricalcolo correttivo totali cantiere
- task_id=40 (wf 36, ProjectMilestone) — aggiunti Extra/Materiali + `cf_940` (MOL%)
- task_id=38 (wf 33, ProjectTask) — aggiunti Extra/Materiali + `cf_940` (MOL%)

---

# AGGIORNAMENTO 2026-06-16 — Materiali (SalesOrder), MOL/MOL%, Extra, e mappa completa workflow

> Sessione dedicata a: tab "Materiali" sul cantiere, propagazione del Costo Materiali da SalesOrder,
> gestione di Ricavo/Costo Extra e MOL/MOL%, e diagnosi di un bug di aggregazione su lavorazioni cestinate.
> **Usare questa sezione + le mappe campi sopra come base di conoscenza per future evoluzioni.**

## A. ⚠️ `workflow_id` ≠ `task_id` — non confonderli
Ogni workflow (`com_vtiger_workflows.workflow_id`) ha uno o più task (`com_vtiger_workflowtasks.task_id`).
I numeri NON coincidono e si accavallano (esiste `workflow_id=38` su Project E `task_id=38` del wf 33).
**Quando si modifica una query SQL si lavora sempre per `task_id`.**

## B. Mappa COMPLETA dei workflow che toccano i totali cantiere
| modulo (trigger) | workflow_id | task_id | summary | tipo | cosa fa |
|---|---|---|---|---|---|
| ProjectTask | 33 | **38** | Aggiorna costo totale sul cantiere | VTDatabaseQueryTask | giornata→lavorazione→cantiere (milestone-based) |
| ProjectMilestone | 36 | **40** | aggiorna i totali | VTDatabaseQueryTask | lavorazione→cantiere (milestone-based) |
| SalesOrder | 43 | **49** | Aggiorna costo materiali sul cantiere | VTDatabaseQueryTask | scrive `cf_942` + ricalcola totali cantiere (correttivo) |
| Project | 29 | 33 | Calcolo MOL | VTUpdateFieldsTask | `cf_883 = cf_881 - cf_879` |
| Project | 39 | 44 | Aggiorna MOL % | VTUpdateFieldsTask | `cf_940 = 100 * cf_883 / cf_881` |
| Project | 40 | 47 | Crea Lavorazione | VTCreateEntityTask | crea ProjectMilestone (⚠️ `projectid` HARDCODED a 629 nel mapping — sospetto bug) |
| Project | 42 | 48 | Crea Bolla Materiali Cantiere | VTCreateEntityTask | crea/upsert un SalesOrder con `subject = projectname` |
| Project | 38 | 43 | Cambia Assegnato | VTUpdateFieldsTask | `assigned_user_id = 20` |

**Cascate importanti (catene di trigger):**
- Salvare un **Project** → wf42 crea/salva il **SalesOrder** omonimo → scatta **wf43** sul SalesOrder.
- Quindi "salvo il cantiere" finisce per ricalcolare i totali via wf43. Tienine conto: i totali cantiere
  possono essere riscritti da 3 strade diverse (wf33 da giornata, wf36 da lavorazione, wf43 da SalesOrder/Project).

## C. Formula STANDARD dei totali cantiere (replicata IDENTICA in wf33/36/43)
L'UPDATE finale su `vtiger_projectcf` deve essere lo stesso nei 3 workflow:
```sql
SET cf_879 = <SUM(milestone cf_906)> + COALESCE(cf_938,0) + COALESCE(cf_942,0),   -- costi + Costo Extra + Costo Materiali
    cf_881 = <SUM(milestone cf_908)> + COALESCE(cf_936,0),                         -- ricavi + Ricavo Extra
    cf_883 = (ricavi) - (costi),                                                   -- MOL
    cf_940 = CASE WHEN cf_881 = 0 THEN 0 ELSE cf_883 / cf_881 * 100 END            -- MOL% (usa cf_881/cf_883 appena assegnati, single-table left-to-right)
WHERE projectid = ...;
```
- MOL e MOL% sono calcolati ANCHE dai workflow di Project (wf29, wf39): ridondante ma coerente (stessa matematica).
- Se evolvi la formula, aggiorna **tutti e tre** i workflow SQL, altrimenti salvare entità diverse produce totali diversi.

## D. 🐞 BUG TROVATO E RISOLTO OGGI — lavorazioni cestinate ("unlinked") nei SUM
**Sintomo:** su un cantiere senza lavorazioni attive comparivano ricavi "fantasma"; salvando il Project i ricavi
tornavano (es. 5000), salvando la giornata sparivano → flip-flop.
**Causa radice:** una lavorazione (ProjectMilestone) **soft-deleted** (`vtiger_crmentity.deleted=1`, in pratica
"unlinked"/cestinata) con la sua giornata ancora attiva (giornata **orfana**). La regola di business è:
**le giornate NON valgono se la loro lavorazione (match type + zona) non esiste/è attiva.**
**Il bug** era nella query SQL del wf43 (correttivo) che usava:
```sql
LEFT JOIN vtiger_crmentity e ON e.crmid = pm.projectmilestoneid AND e.deleted = 0
LEFT JOIN vtiger_projectmilestonecf pmcf ON pmcf.projectmilestoneid = pm.projectmilestoneid  -- ❌ pmcf legato a pm, NON a e
```
Il filtro `deleted=0` stava sul LEFT JOIN di `e`, ma `pmcf` si univa a `pm` a prescindere → la lavorazione
cestinata veniva comunque sommata.
**Fix (pattern corretto):** filtrare i deleted con **INNER JOIN + WHERE `e.deleted=0`** (come wf33/wf36) oppure,
quando serve restituire comunque la riga progetto, usare **subquery correlate**:
```sql
COALESCE((SELECT SUM(pmcf.cf_908)
          FROM vtiger_projectmilestone pm
          INNER JOIN vtiger_crmentity e ON e.crmid=pm.projectmilestoneid AND e.deleted=0
          INNER JOIN vtiger_projectmilestonecf pmcf ON pmcf.projectmilestoneid=pm.projectmilestoneid
          WHERE pm.projectid = p.projectid), 0) AS totale_ricavi
```
**Regola generale:** in ogni aggregazione su lavorazioni/giornate filtrare SEMPRE `vtiger_crmentity.deleted=0`
in modo che escluda davvero la riga dal SUM (mai un LEFT JOIN col deleted sul solo crmentity).
**TODO dati (rimandato):** bonificare le **giornate orfane** (giornata la cui lavorazione type+zona non esiste
più tra le lavorazioni attive): rilink a una lavorazione valida oppure eliminazione.

## E. Feature "Tab Materiali" sul cantiere (solo flavor `construction`)
Mostra il contenuto reale di un SalesOrder (line item = materiali) dentro il Detail del Project, restando nel contesto cantiere.
- **`lib/modules/Project/models/DetailView.php`** → `getDetailViewRelatedLinks()`: aggiunge una tab `DETAILVIEWTAB`
  "Materiali" (`...&mode=showMaterials`) SOLO se `getenv('TM_FLAVOR')==='construction'`, l'utente ha privilegi
  DetailView su SalesOrder, ed esiste un SalesOrder con `subject = projectname`.
- **`lib/modules/Project/views/Detail.php`** → `exposeMethod('showMaterials')`: riscrive la request a
  `module=SalesOrder`, `record=<id>`, `requestMode=full` e delega a `SalesOrder_Detail_View::showDetailViewByMode`
  (Inventory renderizza le line item). ⚠️ Imposta anche `$_REQUEST['materialsProjectId']` (vedi gotcha G).
- **`lib/layouts/v7/modules/Inventory/LineItemsDetail.tpl`** → bottone "Aggiungi Materiali" (solo se
  `$smarty.request.mode eq 'showMaterials'`) che apre la Edit del SalesOrder con i parametri `return*` per
  tornare alla tab Materiali dopo il salvataggio.
- **`lib/layouts/v7/modules/Project/resources/Detail.js`** → al load, se l'URL contiene "Materiali", clicca la tab.
- Costo Materiali del cantiere = `cf_942`, scritto dal wf43 = `SalesOrder.total` del SalesOrder agganciato per nome.

## F. Display "%" sui campi percentuale (Summary/Detail)
`lib/layouts/v7/modules/Vtiger/uitypes/StringDetailView.tpl` → aggiunto ramo
`{else if $FIELD_MODEL->getFieldDataType() eq 'percentage'} ... &nbsp;%`. Mostra "55,00 %" su tutti i campi
percentuale (es. MOL% cf_940) in Summary e Detail. L'edit inline non è toccato (il `%` in edit arriva
dall'addon di `uitypes/Percentage.tpl`).

## G. Gotcha utili
- **`$smarty.request` = `$_REQUEST`**, NON l'oggetto `Vtiger_Request`. `$request->set('x', v)` NON popola
  `$smarty.request.x`. Per passare un valore a un template via `$smarty.request` impostare `$_REQUEST['x']`.
- **Redirect dopo Save** (vtiger): se la Edit ha `returnview` → `setViewerReturnValues()` popola i campi hidden
  `return*`; `Vtiger_Save_Action` usa `getReturnURL()` (strippa il prefisso `return`) se c'è `returntab_label`
  oppure `returnmodule && returnview`. Solo i `return*` predefiniti sopravvivono come hidden field.
- **Le tab del Detail** sono link `DETAILVIEWTAB` (modo custom via `mode=...`) come il Gantt (`showChart`);
  il JS `loadContents(data-url)` inietta l'HTML restituito dall'exposeMethod.
- **File duplicati**: vedi memoria `project_duplicate_files.md` — molti file `lib/...` hanno gemello in
  `lib/pkg/vtiger/...` da sincronizzare con `cp`. Le modifiche `.tpl`/`.php`/`.js` di UI vanno deployate
  sull'istanza (non bastano sul DB come le query workflow).

## H. ws entity id (per `vtc revise` e formato `<id>x<crmid>`)
| Modulo | ws id |
|---|---|
| ProjectMilestone | 29 |
| ProjectTask | 30 |
| Project | 31 |
| SalesOrder | 3 |
`vtc revise <Modulo> '{"id":"<wsid>x<crmid>", ...}'` salva via webservice e **scatta i workflow** (utile per test).

## I. Script `contrib/` aggiunti oggi (dry-run default, `--apply` per scrivere; pattern UNHEX + validazione PHP)
- `update_wf43_materiali.py` — prima versione wf43 (solo `cf_942`).
- `update_wf43_corrective.py` — wf43 correttivo (cf_942 + ricalcolo totali/MOL/MOL%, con fix lavorazioni deleted).
- `update_wf36_extra.py` / `update_wf33_extra.py` — aggiungono Ricavo/Costo Extra + Materiali ai totali cantiere.
- `add_mol_pct.py` — aggiunge il calcolo `cf_940` (MOL%) all'UPDATE finale di wf33 (task 38) e wf36 (task 40).
