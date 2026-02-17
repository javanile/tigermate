#!/usr/bin/env bash
set -e

if [ -z "$1" ]; then
  echo "ATTENTION: You can lost all data! Type 'YES' to continue:"
  read AGREE

  if [ "${AGREE}" = "YES" ]; then
    rm lib/config.inc.php
    touch lib/config.inc.php
    docker compose exec mysql bash -c '
      mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" -e "
      SET FOREIGN_KEY_CHECKS = 0;
      SET GROUP_CONCAT_MAX_LEN=32768;
      SELECT GROUP_CONCAT(CONCAT(\"DROP TABLE IF EXISTS \\\`\", table_name, \"\\\`\") SEPARATOR \"; \")
      INTO @tables
      FROM information_schema.tables
      WHERE table_schema = \"$MYSQL_DATABASE\";
      SET @tables = CONCAT(@tables, \";\");
      PREPARE stmt FROM @tables;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
      SET FOREIGN_KEY_CHECKS = 1;
      "
      '
  fi

  exit 0
fi

echo "Connecting to: $1"

variables=$(grep "^crm=$1 " .hosts | head -1)

if [ -z "$variables" ]; then
    echo "No such host: $1"
    exit 1
fi

for variable in $variables; do
  declare "$variable"
done

sshpass -p "${ssh_password}" ssh -t "${ssh_user}@${ssh_host}" -p "${ssh_port:-22}" "cd /opt/$crm && make reset"
