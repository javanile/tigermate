#!/usr/bin/env python3
"""Workflow 42 (task 48, "Crea Bolla Materiali Cantiere", VTCreateEntityTask) su medipav.

Cambia la mappatura del campo assigned_user_id del SalesOrder creato/aggiornato:
da rawtext "admin"  ->  fieldname "assigned_user_id" del cantiere (Project).
Cosi' il record materiali eredita l'assegnatario del cantiere.

Dry-run di default; --apply per scrivere (pattern UNHEX).
"""
import subprocess, json, re, html, os, sys

TASK_ID = 48

OLD = '{"fieldname":"assigned_user_id","value":"admin","valuetype":"rawtext","modulename":"Project"}'
NEW = '{"fieldname":"assigned_user_id","value":"assigned_user_id","valuetype":"fieldname","modulename":"Project"}'


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


def php_validate(serialized):
    # serialized puo' contenere null byte (proprieta' protette) -> passo via stdin, non argv
    php = ('$s = file_get_contents("php://stdin");'
           '$o = unserialize($s, ["allowed_classes"=>false]);'
           'if ($o === false) { fwrite(STDERR,"INVALID"); exit(1); }'
           '$a=(array)$o; echo $a["field_value_mapping"];')
    r = subprocess.run(['php', '-r', php], input=serialized.encode('utf-8'),
                       capture_output=True)
    if r.returncode != 0:
        sys.exit(f"PHP unserialize FALLITO: {r.stderr.decode(errors='replace')}")
    return r.stdout.decode('utf-8', errors='replace')


def main():
    apply = '--apply' in sys.argv
    raw = get_task(TASK_ID)

    m = re.search(r's:19:"field_value_mapping";s:(\d+):"(\[.*?\])";', raw, re.DOTALL)
    if not m:
        sys.exit("field_value_mapping non trovato")
    fvm = m.group(2)

    if NEW.split('"value":')[1] in fvm and OLD not in fvm:
        print("Gia' applicato (assigned_user_id = fieldname). Nulla da fare.")
        return
    if fvm.count(OLD) != 1:
        sys.exit(f"Substring assigned_user_id trovata {fvm.count(OLD)} volte (atteso 1). Abort.")

    new_fvm = fvm.replace(OLD, NEW)
    new_len = len(new_fvm.encode('utf-8'))
    new_raw = (raw[:m.start()]
               + f's:19:"field_value_mapping";s:{new_len}:"{new_fvm}";'
               + raw[m.end():])

    back = php_validate(new_raw)
    ok = (NEW in back)
    print(f"task_id = {TASK_ID}")
    print(f"len fvm: {m.group(1)} -> {new_len}")
    print(f"PHP unserialize OK; entry aggiornata presente = {ok}")
    print("nuova mappatura assigned_user_id:")
    print("  " + NEW)

    if not apply:
        print("\n[DRY-RUN] niente scritto. Rilancia con --apply.")
        return

    hex_val = new_raw.encode('utf-8').hex()
    sql = f"UPDATE com_vtiger_workflowtasks SET task = UNHEX('{hex_val}') WHERE task_id = {TASK_ID}"
    res = vtc(['control', 'db', 'execute', sql])
    print("\naffectedRows =", res['result']['affectedRows'])


if __name__ == '__main__':
    main()
