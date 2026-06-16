#!/usr/bin/env python3
"""Workflow 36 (task 40) su medipav: includere Ricavo Extra (cf_936),
Costo Extra (cf_938) e Costo Materiali (cf_942) nei totali del cantiere.

Modifica SOLO l'ultimo statement (UPDATE vtiger_projectcf).
Dry-run di default; --apply per scrivere (pattern UNHEX).
"""
import subprocess, json, re, html, os, sys

WORKFLOW_ID = 33

OLD_BLOCK = """SET cf_879 = $_row[totale_costi],
    cf_881 = $_row[totale_ricavi],
    cf_883 = $_row[totale_ricavi] - $_row[totale_costi]
WHERE projectid = $_row[projectid];"""

NEW_BLOCK = """SET cf_879 = $_row[totale_costi] + COALESCE(cf_938, 0) + COALESCE(cf_942, 0),
    cf_881 = $_row[totale_ricavi] + COALESCE(cf_936, 0),
    cf_883 = ($_row[totale_ricavi] + COALESCE(cf_936, 0)) - ($_row[totale_costi] + COALESCE(cf_938, 0) + COALESCE(cf_942, 0))
WHERE projectid = $_row[projectid];"""


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


def extract_query(raw):
    return re.search(r's:5:"query";s:\d+:"(.*?)";s:19:', raw, re.DOTALL).group(1)


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
    cur_query = extract_query(raw)

    cnt = cur_query.count(OLD_BLOCK)
    if cnt != 1:
        sys.exit(f"Blocco da sostituire trovato {cnt} volte (atteso 1). Abort.")
    new_query = cur_query.replace(OLD_BLOCK, NEW_BLOCK)

    new_serialized = replace_query_in_serialized(raw, new_query)
    back = php_validate(new_serialized)

    print(f"task_id = {task_id}")
    print("--- ultimo statement DOPO la modifica ---")
    print(NEW_BLOCK)
    print("\n--- PHP unserialize OK; verifica blocco presente nel risultato:",
          (NEW_BLOCK in back))

    if not apply:
        print("\n[DRY-RUN] niente scritto. Rilancia con --apply.")
        return

    hex_val = new_serialized.encode('utf-8').hex()
    sql = f"UPDATE com_vtiger_workflowtasks SET task = UNHEX('{hex_val}') WHERE task_id = {task_id}"
    res = vtc(['control', 'db', 'execute', sql])
    print("\n--- risultato UPDATE ---")
    print(json.dumps(res['result'], indent=2, ensure_ascii=False))


if __name__ == '__main__':
    main()
