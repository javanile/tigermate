#!/usr/bin/env python3
"""Aggiorna la custom query del workflow 43 (task_id 49) su medipav.

Scrive in vtiger_projectcf.cf_942 ("Costo Materiali") del cantiere il totale
del SalesOrder che fa da trigger, agganciando SalesOrder.subject = Project.projectname.

Dry-run (default): rigenera e valida il serializzato, NON scrive.
--apply: scrive sul DB via UNHEX.
"""
import subprocess, json, re, html, os, sys

WORKFLOW_ID = 43

NEW_QUERY = """-- Aggiorna il Costo Materiali del cantiere col totale del SalesOrder salvato (subject = projectname)
UPDATE vtiger_projectcf pcf
INNER JOIN vtiger_project p ON p.projectid = pcf.projectid
INNER JOIN vtiger_crmentity pe ON pe.crmid = p.projectid AND pe.deleted = 0
INNER JOIN vtiger_salesorder so ON so.subject = p.projectname
SET pcf.cf_942 = COALESCE(so.total, 0)
WHERE so.salesorderid = $_id"""


def vtc(args, write=False):
    cmd = ['vtc'] + args
    r = subprocess.run(cmd, capture_output=True, text=True,
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
        sys.exit(f"Sostituzione fallita: {n} match (atteso 1)")
    return out


def php_validate(serialized):
    """Verifica che il serializzato sia unserializzabile da PHP."""
    php = ("$o = unserialize($argv[1]);"
           "if ($o === false) { fwrite(STDERR, 'INVALID'); exit(1); }"
           "echo $o->query;")
    r = subprocess.run(['php', '-r', php, serialized], capture_output=True, text=True)
    if r.returncode != 0:
        sys.exit(f"PHP unserialize FALLITO: {r.stderr}")
    return r.stdout


def main():
    apply = '--apply' in sys.argv
    task_id, raw = get_task(WORKFLOW_ID)
    print(f"task_id = {task_id}")
    print(f"--- query attuale (serializzato grezzo) ---\n{raw}\n")

    new_serialized = replace_query_in_serialized(raw, NEW_QUERY)
    extracted = php_validate(new_serialized)
    print("--- PHP unserialize OK, query estratta dopo modifica ---")
    print(extracted)
    print("\n--- serializzato nuovo ---")
    print(new_serialized)

    if not apply:
        print("\n[DRY-RUN] niente scritto. Rilancia con --apply per applicare.")
        return

    hex_val = new_serialized.encode('utf-8').hex()
    sql = f"UPDATE com_vtiger_workflowtasks SET task = UNHEX('{hex_val}') WHERE task_id = {task_id}"
    res = vtc(['control', 'db', 'execute', sql], write=True)
    print("\n--- risultato UPDATE ---")
    print(json.dumps(res, indent=2, ensure_ascii=False))


if __name__ == '__main__':
    main()
