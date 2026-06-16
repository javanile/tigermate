#!/usr/bin/env python3
"""Rende correttivo il workflow 43 (task 49) su medipav: dopo aver scritto
cf_942 (Costo Materiali) col totale del SalesOrder, ricalcola i totali del
cantiere agganciato (cf_879 costi, cf_881 ricavi, cf_883 MOL, cf_940 MOL%).

Dry-run di default; --apply per scrivere.
"""
import subprocess, json, re, html, os, sys

WORKFLOW_ID = 43

NEW_QUERY = """-- Aggiorna il Costo Materiali del cantiere col totale del SalesOrder salvato (subject = projectname)
UPDATE vtiger_projectcf pcf
INNER JOIN vtiger_project p ON p.projectid = pcf.projectid
INNER JOIN vtiger_crmentity pe ON pe.crmid = p.projectid AND pe.deleted = 0
INNER JOIN vtiger_salesorder so ON so.subject = p.projectname
SET pcf.cf_942 = COALESCE(so.total, 0)
WHERE so.salesorderid = $_id;

-- Ricava il cantiere agganciato e somma i totali delle sue lavorazioni (solo lavorazioni NON eliminate)
SELECT
  p.projectid AS projectid,
  COALESCE((
    SELECT SUM(pmcf.cf_906)
    FROM vtiger_projectmilestone pm
    INNER JOIN vtiger_crmentity e ON e.crmid = pm.projectmilestoneid AND e.deleted = 0
    INNER JOIN vtiger_projectmilestonecf pmcf ON pmcf.projectmilestoneid = pm.projectmilestoneid
    WHERE pm.projectid = p.projectid
  ), 0) AS totale_costi,
  COALESCE((
    SELECT SUM(pmcf.cf_908)
    FROM vtiger_projectmilestone pm
    INNER JOIN vtiger_crmentity e ON e.crmid = pm.projectmilestoneid AND e.deleted = 0
    INNER JOIN vtiger_projectmilestonecf pmcf ON pmcf.projectmilestoneid = pm.projectmilestoneid
    WHERE pm.projectid = p.projectid
  ), 0) AS totale_ricavi
FROM vtiger_salesorder so
INNER JOIN vtiger_project p ON p.projectname = so.subject
INNER JOIN vtiger_crmentity pe ON pe.crmid = p.projectid AND pe.deleted = 0
WHERE so.salesorderid = $_id
LIMIT 1;

-- Aggiorna i totali del cantiere: costi, ricavi, MOL e MOL%
UPDATE vtiger_projectcf
SET cf_879 = $_row[totale_costi] + COALESCE(cf_938, 0) + COALESCE(cf_942, 0),
    cf_881 = $_row[totale_ricavi] + COALESCE(cf_936, 0),
    cf_883 = ($_row[totale_ricavi] + COALESCE(cf_936, 0)) - ($_row[totale_costi] + COALESCE(cf_938, 0) + COALESCE(cf_942, 0)),
    cf_940 = CASE WHEN cf_881 = 0 THEN 0 ELSE cf_883 / cf_881 * 100 END
WHERE projectid = $_row[projectid]"""


def vtc(args):
    r = subprocess.run(['vtc'] + args, capture_output=True, text=True,
                       env={**os.environ, 'CRM': 'medipav'})
    if r.returncode != 0:
        sys.exit(f"vtc error: {r.stderr or r.stdout}")
    return json.loads(r.stdout)


def get_task(workflow_id):
    data = vtc(['inspect', 'db', 'query',
                f"SELECT task_id, task FROM com_vtiger_workflowtasks WHERE workflow_id = {workflow_id}"])
    row = data['result']['rows'][0]
    return row['task_id'], html.unescape(row['task'])


def replace_query_in_serialized(raw, new_query):
    new_len = len(new_query.encode('utf-8'))
    out, n = re.subn(
        r's:5:"query";s:\d+:".*?";(s:\d+:"(?:field_value_mapping|id)")',
        lambda m: f's:5:"query";s:{new_len}:"{new_query}";{m.group(1)}',
        raw, flags=re.DOTALL)
    if n != 1:
        sys.exit(f"Sostituzione query fallita: {n} match")
    return out


def php_validate(serialized):
    php = ('$o = unserialize($argv[1], ["allowed_classes"=>false]);'
           'if ($o === false) { fwrite(STDERR,"INVALID"); exit(1); }'
           '$a=(array)$o; echo $a["query"];')
    r = subprocess.run(['php', '-r', php, serialized], capture_output=True, text=True)
    if r.returncode != 0:
        sys.exit(f"PHP unserialize FALLITO: {r.stderr}")
    return r.stdout


def main():
    apply = '--apply' in sys.argv
    task_id, raw = get_task(WORKFLOW_ID)
    new_serialized = replace_query_in_serialized(raw, NEW_QUERY)
    back = php_validate(new_serialized)
    print(f"task_id = {task_id}")
    print("--- query verificata uguale a quella attesa:", back == NEW_QUERY)
    if not apply:
        print("\n[DRY-RUN] niente scritto. Rilancia con --apply.")
        return
    hex_val = new_serialized.encode('utf-8').hex()
    sql = f"UPDATE com_vtiger_workflowtasks SET task = UNHEX('{hex_val}') WHERE task_id = {task_id}"
    res = vtc(['control', 'db', 'execute', sql])
    print("affectedRows =", res['result']['affectedRows'])


if __name__ == '__main__':
    main()
