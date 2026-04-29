#!/usr/bin/env python3
import argparse
import hashlib
import html
import json
import os
import re
import subprocess
import sys


WORKFLOW_SQL = {
    33: """-- Recupera progetto, tipo, zona e dati della giornata modificata
SELECT
  pt.projectid,
  pt.projecttasktype,
  IFNULL(NULLIF(ptcf.cf_918, ''), '__EMPTY__') AS zona_lavorazione_key,
  COALESCE(ptcf.cf_887, 0) AS numero_operai,
  COALESCE(ptcf.cf_893, 0) AS spese,
  COALESCE(ptcf.cf_877, 0) AS quantita_eseguita
FROM vtiger_projecttask pt
INNER JOIN vtiger_projecttaskcf ptcf ON ptcf.projecttaskid = pt.projecttaskid
WHERE pt.projecttaskid = $_id;

-- Recupera la lavorazione associata al tipo e alla zona lavorazione della giornata
SELECT
  '$_row[projectid]' AS projectid,
  '$_row[projecttasktype]' AS projecttasktype,
  '$_row[zona_lavorazione_key]' AS zona_lavorazione_key,
  '$_row[numero_operai]' AS numero_operai,
  '$_row[spese]' AS spese,
  '$_row[quantita_eseguita]' AS quantita_eseguita,
  COALESCE((
    SELECT m.projectmilestoneid
    FROM vtiger_projectmilestone m
    INNER JOIN vtiger_crmentity e ON e.crmid = m.projectmilestoneid
    INNER JOIN vtiger_projectmilestonecf mcf ON mcf.projectmilestoneid = m.projectmilestoneid
    WHERE m.projectid = '$_row[projectid]'
      AND m.projectmilestonetype = '$_row[projecttasktype]'
      AND IFNULL(NULLIF(mcf.cf_916, ''), '__EMPTY__') = '$_row[zona_lavorazione_key]'
      AND e.deleted = 0
    LIMIT 1
  ), 0) AS projectmilestoneid,
  COALESCE((
    SELECT pmcf.cf_875
    FROM vtiger_projectmilestone m
    INNER JOIN vtiger_crmentity e ON e.crmid = m.projectmilestoneid
    INNER JOIN vtiger_projectmilestonecf pmcf ON pmcf.projectmilestoneid = m.projectmilestoneid
    WHERE m.projectid = '$_row[projectid]'
      AND m.projectmilestonetype = '$_row[projecttasktype]'
      AND IFNULL(NULLIF(pmcf.cf_916, ''), '__EMPTY__') = '$_row[zona_lavorazione_key]'
      AND e.deleted = 0
    LIMIT 1
  ), 0) AS costo_operaio,
  COALESCE((
    SELECT pmcf.cf_885
    FROM vtiger_projectmilestone m
    INNER JOIN vtiger_crmentity e ON e.crmid = m.projectmilestoneid
    INNER JOIN vtiger_projectmilestonecf pmcf ON pmcf.projectmilestoneid = m.projectmilestoneid
    WHERE m.projectid = '$_row[projectid]'
      AND m.projectmilestonetype = '$_row[projecttasktype]'
      AND IFNULL(NULLIF(pmcf.cf_916, ''), '__EMPTY__') = '$_row[zona_lavorazione_key]'
      AND e.deleted = 0
    LIMIT 1
  ), 0) AS prezzo_unitario,
  COALESCE((
    SELECT pmcf.cf_904
    FROM vtiger_projectmilestone m
    INNER JOIN vtiger_crmentity e ON e.crmid = m.projectmilestoneid
    INNER JOIN vtiger_projectmilestonecf pmcf ON pmcf.projectmilestoneid = m.projectmilestoneid
    WHERE m.projectid = '$_row[projectid]'
      AND m.projectmilestonetype = '$_row[projecttasktype]'
      AND IFNULL(NULLIF(pmcf.cf_916, ''), '__EMPTY__') = '$_row[zona_lavorazione_key]'
      AND e.deleted = 0
    LIMIT 1
  ), 0) AS totale_da_lavorare,
  COALESCE((
    SELECT m.projectmilestonedate
    FROM vtiger_projectmilestone m
    INNER JOIN vtiger_crmentity e ON e.crmid = m.projectmilestoneid
    INNER JOIN vtiger_projectmilestonecf pmcf ON pmcf.projectmilestoneid = m.projectmilestoneid
    WHERE m.projectid = '$_row[projectid]'
      AND m.projectmilestonetype = '$_row[projecttasktype]'
      AND IFNULL(NULLIF(pmcf.cf_916, ''), '__EMPTY__') = '$_row[zona_lavorazione_key]'
      AND e.deleted = 0
    LIMIT 1
  ), NULL) AS projectmilestonedate,
  COALESCE((
    SELECT m.projectmilestonedeliverydate
    FROM vtiger_projectmilestone m
    INNER JOIN vtiger_crmentity e ON e.crmid = m.projectmilestoneid
    INNER JOIN vtiger_projectmilestonecf pmcf ON pmcf.projectmilestoneid = m.projectmilestoneid
    WHERE m.projectid = '$_row[projectid]'
      AND m.projectmilestonetype = '$_row[projecttasktype]'
      AND IFNULL(NULLIF(pmcf.cf_916, ''), '__EMPTY__') = '$_row[zona_lavorazione_key]'
      AND e.deleted = 0
    LIMIT 1
  ), NULL) AS projectmilestonedeliverydate;

-- Aggiorna i parziali sulla giornata modificata
UPDATE vtiger_projecttaskcf
SET cf_922 = COALESCE('$_row[spese]', 0) + (COALESCE('$_row[numero_operai]', 0) * COALESCE('$_row[costo_operaio]', 0)),
    cf_920 = COALESCE('$_row[quantita_eseguita]', 0) * COALESCE('$_row[prezzo_unitario]', 0),
    cf_926 = (
      COALESCE('$_row[quantita_eseguita]', 0) * COALESCE('$_row[prezzo_unitario]', 0)
    ) - (
      COALESCE('$_row[spese]', 0) + (COALESCE('$_row[numero_operai]', 0) * COALESCE('$_row[costo_operaio]', 0))
    )
WHERE projecttaskid = $_id;

-- Ricalcola i totali della lavorazione partendo dalle giornate dello stesso tipo e zona
SELECT
  '$_row[projectid]' AS projectid,
  '$_row[projectmilestoneid]' AS projectmilestoneid,
  COUNT(pt.projecttaskid) AS avanzamento_giornate,
  COALESCE(SUM(ptcf.cf_877), 0) AS avanzamento,
  COALESCE(SUM(ptcf.cf_893), 0) AS totale_spese,
  COALESCE(SUM(ptcf.cf_922), 0) AS totale_costi,
  COALESCE(SUM(ptcf.cf_920), 0) AS totale_ricavi,
  CASE
    WHEN COALESCE('$_row[totale_da_lavorare]', 0) = 0 THEN 0
    ELSE (COALESCE(SUM(ptcf.cf_877), 0) / '$_row[totale_da_lavorare]') * 100
  END AS avanzamento_percentuale,
  CASE
    WHEN '$_row[projectmilestonedate]' IS NULL OR '$_row[projectmilestonedeliverydate]' IS NULL
      OR DATEDIFF('$_row[projectmilestonedeliverydate]', '$_row[projectmilestonedate]') < 0 THEN 0
    ELSE (COUNT(pt.projecttaskid) / (DATEDIFF('$_row[projectmilestonedeliverydate]', '$_row[projectmilestonedate]') + 1)) * 100
  END AS avanzamento_giornate_percentuale
FROM vtiger_projecttask pt
INNER JOIN vtiger_crmentity e ON e.crmid = pt.projecttaskid
INNER JOIN vtiger_projecttaskcf ptcf ON ptcf.projecttaskid = pt.projecttaskid
WHERE pt.projectid = '$_row[projectid]'
  AND pt.projecttasktype = '$_row[projecttasktype]'
  AND IFNULL(NULLIF(ptcf.cf_918, ''), '__EMPTY__') = '$_row[zona_lavorazione_key]'
  AND e.deleted = 0;

-- Aggiorna la lavorazione corrispondente
UPDATE vtiger_projectmilestonecf
SET cf_867 = $_row[avanzamento],
    cf_897 = $_row[totale_spese],
    cf_906 = $_row[totale_costi],
    cf_908 = $_row[totale_ricavi],
    cf_928 = $_row[totale_ricavi] - $_row[totale_costi],
    cf_910 = $_row[avanzamento_giornate],
    cf_912 = $_row[avanzamento_percentuale],
    cf_914 = $_row[avanzamento_giornate_percentuale]
WHERE projectmilestoneid = $_row[projectmilestoneid];

-- Ricalcola i totali del progetto sommando tutte le lavorazioni
SELECT
  '$_row[projectid]' AS projectid,
  COALESCE(SUM(pmcf.cf_906), 0) AS totale_costi,
  COALESCE(SUM(pmcf.cf_908), 0) AS totale_ricavi
FROM vtiger_projectmilestone pm
INNER JOIN vtiger_crmentity e ON e.crmid = pm.projectmilestoneid
INNER JOIN vtiger_projectmilestonecf pmcf ON pmcf.projectmilestoneid = pm.projectmilestoneid
WHERE pm.projectid = '$_row[projectid]'
  AND e.deleted = 0;

-- Aggiorna i totali sul cantiere
UPDATE vtiger_projectcf
SET cf_879 = $_row[totale_costi],
    cf_881 = $_row[totale_ricavi],
    cf_883 = $_row[totale_ricavi] - $_row[totale_costi]
WHERE projectid = $_row[projectid];""",
    36: """-- Recupera la lavorazione modificata con tipo e zona lavorazione
SELECT
  pm.projectmilestoneid,
  pm.projectid,
  pm.projectmilestonetype,
  IFNULL(NULLIF(pmcf.cf_916, ''), '__EMPTY__') AS zona_lavorazione_key,
  COALESCE(pmcf.cf_875, 0) AS costo_operaio,
  COALESCE(pmcf.cf_885, 0) AS prezzo_unitario,
  COALESCE(pmcf.cf_904, 0) AS totale_da_lavorare,
  pm.projectmilestonedate,
  pm.projectmilestonedeliverydate
FROM vtiger_projectmilestone pm
INNER JOIN vtiger_projectmilestonecf pmcf ON pmcf.projectmilestoneid = pm.projectmilestoneid
WHERE pm.projectmilestoneid = $_id;

-- Aggiorna i parziali di tutte le giornate della stessa lavorazione
UPDATE vtiger_projecttaskcf ptcf
INNER JOIN vtiger_projecttask pt ON pt.projecttaskid = ptcf.projecttaskid
INNER JOIN vtiger_crmentity e ON e.crmid = pt.projecttaskid
SET ptcf.cf_922 = COALESCE(ptcf.cf_893, 0) + (COALESCE(ptcf.cf_887, 0) * COALESCE('$_row[costo_operaio]', 0)),
    ptcf.cf_920 = COALESCE(ptcf.cf_877, 0) * COALESCE('$_row[prezzo_unitario]', 0),
    ptcf.cf_926 = (
      COALESCE(ptcf.cf_877, 0) * COALESCE('$_row[prezzo_unitario]', 0)
    ) - (
      COALESCE(ptcf.cf_893, 0) + (COALESCE(ptcf.cf_887, 0) * COALESCE('$_row[costo_operaio]', 0))
    )
WHERE pt.projectid = '$_row[projectid]'
  AND pt.projecttasktype = '$_row[projectmilestonetype]'
  AND IFNULL(NULLIF(ptcf.cf_918, ''), '__EMPTY__') = '$_row[zona_lavorazione_key]'
  AND e.deleted = 0;

-- Ricalcola i totali della lavorazione partendo dalle giornate dello stesso tipo e zona
SELECT
  '$_row[projectid]' AS projectid,
  '$_row[projectmilestoneid]' AS projectmilestoneid,
  COUNT(pt.projecttaskid) AS avanzamento_giornate,
  COALESCE(SUM(ptcf.cf_877), 0) AS avanzamento,
  COALESCE(SUM(ptcf.cf_893), 0) AS totale_spese,
  COALESCE(SUM(ptcf.cf_922), 0) AS totale_costi,
  COALESCE(SUM(ptcf.cf_920), 0) AS totale_ricavi,
  CASE
    WHEN COALESCE('$_row[totale_da_lavorare]', 0) = 0 THEN 0
    ELSE (COALESCE(SUM(ptcf.cf_877), 0) / '$_row[totale_da_lavorare]') * 100
  END AS avanzamento_percentuale,
  CASE
    WHEN '$_row[projectmilestonedate]' IS NULL OR '$_row[projectmilestonedeliverydate]' IS NULL
      OR DATEDIFF('$_row[projectmilestonedeliverydate]', '$_row[projectmilestonedate]') < 0 THEN 0
    ELSE (COUNT(pt.projecttaskid) / (DATEDIFF('$_row[projectmilestonedeliverydate]', '$_row[projectmilestonedate]') + 1)) * 100
  END AS avanzamento_giornate_percentuale
FROM vtiger_projecttask pt
INNER JOIN vtiger_crmentity e ON e.crmid = pt.projecttaskid
INNER JOIN vtiger_projecttaskcf ptcf ON ptcf.projecttaskid = pt.projecttaskid
WHERE pt.projectid = '$_row[projectid]'
  AND pt.projecttasktype = '$_row[projectmilestonetype]'
  AND IFNULL(NULLIF(ptcf.cf_918, ''), '__EMPTY__') = '$_row[zona_lavorazione_key]'
  AND e.deleted = 0;

-- Aggiorna la lavorazione modificata
UPDATE vtiger_projectmilestonecf
SET cf_867 = $_row[avanzamento],
    cf_897 = $_row[totale_spese],
    cf_906 = $_row[totale_costi],
    cf_908 = $_row[totale_ricavi],
    cf_928 = $_row[totale_ricavi] - $_row[totale_costi],
    cf_910 = $_row[avanzamento_giornate],
    cf_912 = $_row[avanzamento_percentuale],
    cf_914 = $_row[avanzamento_giornate_percentuale]
WHERE projectmilestoneid = $_row[projectmilestoneid];

-- Ricalcola i totali del progetto sommando tutte le lavorazioni
SELECT
  '$_row[projectid]' AS projectid,
  COALESCE(SUM(pmcf.cf_906), 0) AS totale_costi,
  COALESCE(SUM(pmcf.cf_908), 0) AS totale_ricavi
FROM vtiger_projectmilestone pm
INNER JOIN vtiger_crmentity e ON e.crmid = pm.projectmilestoneid
INNER JOIN vtiger_projectmilestonecf pmcf ON pmcf.projectmilestoneid = pm.projectmilestoneid
WHERE pm.projectid = '$_row[projectid]'
  AND e.deleted = 0;

-- Aggiorna i totali sul cantiere
UPDATE vtiger_projectcf
SET cf_879 = $_row[totale_costi],
    cf_881 = $_row[totale_ricavi],
    cf_883 = $_row[totale_ricavi] - $_row[totale_costi]
WHERE projectid = $_row[projectid];""",
}


def run_vtc(args, crm):
    env = dict(os.environ)
    env["CRM"] = crm
    result = subprocess.run(
        ["vtc", *args],
        capture_output=True,
        text=True,
        env=env,
        check=False,
    )
    if result.returncode != 0:
        raise RuntimeError(result.stderr.strip() or result.stdout.strip())
    return json.loads(result.stdout)


def get_task_row(crm, workflow_id):
    sql = (
        "SELECT task_id, task FROM com_vtiger_workflowtasks "
        f"WHERE workflow_id = {workflow_id}"
    )
    payload = run_vtc(["inspect", "db", "query", sql], crm)
    rows = payload["result"]["rows"]
    if not rows:
        raise RuntimeError(f"workflow {workflow_id} not found")
    row = rows[0]
    row["task"] = html.unescape(row["task"])
    return row


def replace_query(serialized_task, new_query):
    pattern = r's:5:"query";s:\d+:".*?";(s:\d+:"(?:field_value_mapping|id)")'
    if not re.search(pattern, serialized_task, flags=re.DOTALL):
        raise RuntimeError("query payload not found in serialized task")
    return re.sub(
        pattern,
        lambda m: f's:5:"query";s:{len(new_query)}:"{new_query}";{m.group(1)}',
        serialized_task,
        count=1,
        flags=re.DOTALL,
    )


def build_update_sql(task_id, serialized_task):
    hex_value = serialized_task.encode("utf-8").hex()
    return (
        "UPDATE com_vtiger_workflowtasks "
        f"SET task = UNHEX('{hex_value}') "
        f"WHERE task_id = {task_id}"
    )


def main():
    parser = argparse.ArgumentParser(
        description="Update workflow SQL for totals on medipav-like CRM instances."
    )
    parser.add_argument("--crm", default=os.environ.get("CRM", "medipav"))
    parser.add_argument("--apply", action="store_true")
    parser.add_argument("--print-sql", action="store_true")
    parser.add_argument("--workflow", type=int, choices=sorted(WORKFLOW_SQL))
    args = parser.parse_args()

    workflow_ids = [args.workflow] if args.workflow else sorted(WORKFLOW_SQL)

    for workflow_id in workflow_ids:
        task_row = get_task_row(args.crm, workflow_id)
        new_task = replace_query(task_row["task"], WORKFLOW_SQL[workflow_id])
        update_sql = build_update_sql(task_row["task_id"], new_task)

        print(f"workflow_id={workflow_id} task_id={task_row['task_id']}")
        print(f"query_length={len(WORKFLOW_SQL[workflow_id])}")
        print(f"serialized_sha1={hashlib.sha1(new_task.encode('utf-8')).hexdigest()}")

        if args.apply:
            payload = run_vtc(["control", "db", "execute", update_sql], args.crm)
            print(json.dumps(payload, indent=2, ensure_ascii=True))
        elif args.print_sql:
            print(update_sql)

        print()

    return 0


if __name__ == "__main__":
    sys.exit(main())
