{*<!--
/*********************************************************************************
** VTDatabaseQueryTask Template
********************************************************************************/
-->*}
<input type="hidden" name="query" id="dbqQuery" value="{$TASK_OBJECT->query|escape:'html'}">

<div id="dbq-wrap">
    <div class="row form-group">
        <div class="col-sm-12">
            <label style="font-weight:600;">SQL Statements</label>
            <textarea id="dbqEditor" style="font-family:monospace;font-size:13px;width:100%;height:220px;padding:8px;border:1px solid #ccc;border-radius:3px;resize:vertical;">{$TASK_OBJECT->query|escape:'html'}</textarea>
            <div style="font-size:11px;color:#888;margin:4px 0 10px;">
                Variabili: <code>$nomecampo</code> → campo del record &nbsp;|&nbsp;
                <code>$_id</code> → crmid del record &nbsp;|&nbsp;
                <code>$_row[col]</code> → prima riga del SELECT precedente &nbsp;|&nbsp;
                <code>$_last_insert_id</code> → id dell'INSERT precedente<br>
                Separare gli statement con <code>;</code> &nbsp;|&nbsp;
                Consentiti: <strong>SELECT, INSERT, UPDATE, REPLACE</strong>
            </div>
        </div>
    </div>

    <div class="row form-group">
        <div class="col-sm-12" style="display:flex;align-items:center;gap:8px;">
            <input type="text" id="dbqTestId" class="inputElement" placeholder="Record ID" style="width:120px;">
            <button type="button" id="dbqTestBtn" class="btn btn-info btn-sm">
                <span class="fa fa-play"></span> Test SQL
            </button>
            <span id="dbqTestSpinner" style="display:none;font-size:12px;color:#888;">esecuzione...</span>
            <span style="color:#ccc;margin:0 4px;">|</span>
            <input type="text" id="dbqTableSearch" class="inputElement" placeholder="Cerca tabella..." style="width:200px;" list="dbqTableList" autocomplete="off">
            <datalist id="dbqTableList"></datalist>
            <button type="button" id="dbqPreviewBtn" class="btn btn-default btn-sm">
                <span class="fa fa-table"></span> Preview
            </button>
            <span id="dbqPreviewSpinner" style="display:none;font-size:12px;color:#888;">caricamento...</span>
        </div>
    </div>

    <div id="dbq-results"></div>
</div>

<script type="text/javascript">
var _dbqModule = '{$WORKFLOW_MODEL->get('module_name')|escape:'javascript'}';
{literal}
(function () {
    jQuery('#dbqEditor').on('input', function () {
        jQuery('#dbqQuery').val(jQuery(this).val());
    });
    jQuery('#saveTask').on('submit', function () {
        jQuery('#dbqQuery').val(jQuery('#dbqEditor').val());
    });

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function renderTable(rows) {
        if (!rows || rows.length === 0) return '<em style="font-size:12px;color:#555">Nessuna riga restituita</em>';
        var cols = Object.keys(rows[0]);
        var h = '<table style="border-collapse:collapse;width:100%;font-size:12px;margin-top:6px"><tr>'
            + cols.map(function (c) { return '<th style="background:#eee;border:1px solid #ccc;padding:4px 6px;text-align:left">' + esc(c) + '</th>'; }).join('') + '</tr>';
        rows.forEach(function (row) {
            h += '<tr>' + cols.map(function (c) { return '<td style="border:1px solid #ddd;padding:3px 6px">' + esc(row[c]) + '</td>'; }).join('') + '</tr>';
        });
        return h + '</table>';
    }

    function renderResults(results) {
        var html = '';
        results.forEach(function (r, i) {
            var badge = r.allowed === false
                ? '<span style="color:#c60">[BLOCCATO]</span>'
                : '<span style="color:#069">[' + esc(r.type) + ']</span>';

            var s = '<div style="border:1px solid #ddd;border-radius:4px;margin:8px 0;padding:10px">';
            s += '<div style="font-weight:bold;font-size:12px;color:#555;margin-bottom:6px">Statement ' + (i + 1) + ' ' + badge + '</div>';
            s += '<div style="background:#f7f7f7;font-family:monospace;font-size:12px;padding:6px;border-radius:3px;white-space:pre-wrap;word-break:break-all;margin-bottom:6px">' + esc(r.resolved) + '</div>';

            if (r.error) {
                s += '<div style="color:#c00;font-size:12px"><span class="fa fa-warning"></span> ' + esc(r.error) + '</div>';
            } else if (r.type === 'SELECT') {
                if (r.row && Object.keys(r.row).length > 0) {
                    s += '<div style="font-size:12px;color:#333;margin:4px 0"><strong>$_row</strong> = <span style="color:#060;font-family:monospace">' + esc(JSON.stringify(r.row)) + '</span></div>';
                }
                s += renderTable(r.rows);
            } else {
                s += '<div style="font-size:12px;color:#333">Righe interessate: <strong>' + r.affected + '</strong></div>';
                if (r.last_insert_id) {
                    s += '<div style="font-size:12px;color:#333"><strong>$_last_insert_id</strong> = <span style="color:#060;font-family:monospace">' + esc(r.last_insert_id) + '</span></div>';
                }
            }

            html += s + '</div>';
        });
        jQuery('#dbq-results').html(html || '<em style="font-size:12px;color:#555">Nessuno statement da eseguire.</em>');
    }

    // Load table list for datalist autocomplete
    jQuery.post('index.php', {
        module: 'Workflows', parent: 'Settings', action: 'DatabaseQueryTest', mode: 'getTables'
    }, function (response) {
        if (response && response.result) {
            var opts = response.result.map(function (t) { return '<option value="' + t + '">'; }).join('');
            jQuery('#dbqTableList').html(opts);
        }
    }, 'json');

    jQuery('#dbqPreviewBtn').on('click', function () {
        var table = jQuery('#dbqTableSearch').val().trim();
        if (!table) { alert('Seleziona o digita il nome di una tabella.'); return; }

        jQuery('#dbqPreviewSpinner').show();
        jQuery('#dbqPreviewBtn').prop('disabled', true);
        jQuery('#dbq-results').html('');

        jQuery.post('index.php', {
            module: 'Workflows', parent: 'Settings', action: 'DatabaseQueryTest',
            mode: 'previewTable', table_name: table
        }, function (response) {
            jQuery('#dbqPreviewSpinner').hide();
            jQuery('#dbqPreviewBtn').prop('disabled', false);
            if (response && response.result) {
                var rows = response.result;
                var header = '<div style="font-weight:bold;font-size:12px;color:#555;margin-bottom:6px">'
                    + 'SELECT * FROM <code>' + esc(table) + '</code> LIMIT 10 '
                    + '<span style="color:#888;font-weight:normal">(' + rows.length + ' righe)</span></div>';
                jQuery('#dbq-results').html('<div style="border:1px solid #ddd;border-radius:4px;padding:10px">' + header + renderTable(rows) + '</div>');
            } else if (response && response.error) {
                var errMsg = (response.error && response.error.message) ? response.error.message : String(response.error);
                jQuery('#dbq-results').html('<div style="color:#c00">' + esc(errMsg) + '</div>');
            }
        }, 'json').fail(function () {
            jQuery('#dbqPreviewSpinner').hide();
            jQuery('#dbqPreviewBtn').prop('disabled', false);
            jQuery('#dbq-results').html('<div style="color:#c00">Errore di comunicazione con il server.</div>');
        });
    });

    jQuery('#dbqTestBtn').on('click', function () {
        var sql      = jQuery('#dbqEditor').val().trim();
        var recordId = jQuery('#dbqTestId').val().trim();

        if (!sql)      { alert('Scrivi almeno uno statement SQL.'); return; }
        if (!recordId) { alert('Inserisci un Record ID per il test.'); return; }

        jQuery('#dbqTestSpinner').show();
        jQuery('#dbqTestBtn').prop('disabled', true);
        jQuery('#dbq-results').html('');

        jQuery.post('index.php', {
            module:      'Workflows',
            parent:      'Settings',
            action:      'DatabaseQueryTest',
            mode:        'run',
            query:       sql,
            record_id:   recordId,
            module_name: _dbqModule
        }, function (response) {
            jQuery('#dbqTestSpinner').hide();
            jQuery('#dbqTestBtn').prop('disabled', false);
            if (response && response.result) {
                renderResults(response.result);
            } else if (response && response.error) {
                var errMsg = (response.error && response.error.message) ? response.error.message : String(response.error);
                jQuery('#dbq-results').html('<div style="color:#c00">' + esc(errMsg) + '</div>');
            }
        }, 'json').fail(function () {
            jQuery('#dbqTestSpinner').hide();
            jQuery('#dbqTestBtn').prop('disabled', false);
            jQuery('#dbq-results').html('<div style="color:#c00">Errore di comunicazione con il server.</div>');
        });
    });
}());
{/literal}
</script>
