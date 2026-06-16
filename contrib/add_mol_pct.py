#!/usr/bin/env python3
"""Aggiunge il calcolo del MOL% (cf_940 = cf_883/cf_881*100) all'UPDATE finale
sul cantiere nei workflow 33 (task 38) e 36 (task 40) su medipav.

Dry-run di default; --apply per scrivere.
"""
import subprocess, json, re, html, os, sys

TASK_IDS = [38, 40]

OLD_BLOCK = """    cf_883 = ($_row[totale_ricavi] + COALESCE(cf_936, 0)) - ($_row[totale_costi] + COALESCE(cf_938, 0) + COALESCE(cf_942, 0))
WHERE projectid = $_row[projectid];"""

NEW_BLOCK = """    cf_883 = ($_row[totale_ricavi] + COALESCE(cf_936, 0)) - ($_row[totale_costi] + COALESCE(cf_938, 0) + COALESCE(cf_942, 0)),
    cf_940 = CASE WHEN cf_881 = 0 THEN 0 ELSE cf_883 / cf_881 * 100 END
WHERE projectid = $_row[projectid];"""


def vtc(args):
    r = subprocess.run(['vtc'] + args, capture_output=True, text=True,
                       env={**os.environ, 'CRM': 'medipav'})
    if r.returncode != 0:
        sys.exit(f"vtc error: {r.stderr or r.stdout}")
    return json.loads(r.stdout)


def get_task(task_id):
    data = vtc(['inspect', 'db', 'query',
                f"SELECT task FROM com_vtiger_workflowtasks WHERE task_id = {task_id}"])
    return html.unescape(data['result']['rows'][0]['task'])


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
    for task_id in TASK_IDS:
        raw = get_task(task_id)
        cur = extract_query(raw)
        if 'cf_940' in cur:
            print(f"task {task_id}: cf_940 GIA' presente, salto.")
            continue
        cnt = cur.count(OLD_BLOCK)
        if cnt != 1:
            sys.exit(f"task {task_id}: blocco trovato {cnt} volte (atteso 1). Abort.")
        new_query = cur.replace(OLD_BLOCK, NEW_BLOCK)
        new_serialized = replace_query_in_serialized(raw, new_query)
        back = php_validate(new_serialized)
        ok = NEW_BLOCK in back
        print(f"task {task_id}: unserialize OK, blocco MOL% presente = {ok}")
        if not apply:
            continue
        hex_val = new_serialized.encode('utf-8').hex()
        sql = f"UPDATE com_vtiger_workflowtasks SET task = UNHEX('{hex_val}') WHERE task_id = {task_id}"
        res = vtc(['control', 'db', 'execute', sql])
        print(f"task {task_id}: affectedRows = {res['result']['affectedRows']}")
    if not apply:
        print("\n[DRY-RUN] niente scritto. Rilancia con --apply.")


if __name__ == '__main__':
    main()
