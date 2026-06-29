/**
 * YFD — Auto sync Dashboard master ke semua sheet pelanggan
 *
 * Pasang di spreadsheet master: "Your Financial Doctor First Aid"
 *
 * SETUP:
 * 1. Extensions → Apps Script → tempel file ini
 * 2. Isi SCRIPT_CONFIG di bawah (URL + token dari Laravel .env)
 * 3. Jalankan sekali: setupDashboardSyncTrigger() → izinkan akses
 * 4. Di tab Dashboard, sel Z1 = versi (contoh: v1.2). Naikkan versi setelah ubah layout/rumus.
 *
 * CATATAN:
 * - Trigger installable WAJIB (simple onEdit tidak bisa panggil URL eksternal)
 * - Hanya edit di tab "Dashboard" yang memicu sync
 * - Debounce 5 menit (script + server) agar tidak spam saat banyak edit
 */

var SCRIPT_CONFIG = {
  laravelWebhookUrl: 'https://yourfinancialdoctor.id/api/dashboard/sync-webhook',
  webhookToken: 'ISI_DASHBOARD_SYNC_WEBHOOK_TOKEN',
  dashboardSheetName: 'Dashboard',
  versionCell: 'Z1',
  debounceMinutes: 5,
};

/**
 * Buat trigger installable (jalankan manual sekali dari editor Apps Script).
 */
function setupDashboardSyncTrigger() {
  var triggers = ScriptApp.getProjectTriggers();
  for (var i = 0; i < triggers.length; i++) {
    if (triggers[i].getHandlerFunction() === 'onDashboardEditInstallable') {
      ScriptApp.deleteTrigger(triggers[i]);
    }
  }
  ScriptApp.newTrigger('onDashboardEditInstallable')
    .forSpreadsheet(SpreadsheetApp.getActive())
    .onEdit()
    .create();
  SpreadsheetApp.getUi().alert(
    'Trigger sync Dashboard aktif.\nEdit tab Dashboard akan memicu webhook ke server YFD.'
  );
}

/**
 * Handler installable — dipanggil Google saat ada edit.
 */
function onDashboardEditInstallable(e) {
  if (!e || !e.range) {
    return;
  }
  var sheet = e.range.getSheet();
  if (sheet.getName() !== SCRIPT_CONFIG.dashboardSheetName) {
    return;
  }

  var props = PropertiesService.getScriptProperties();
  var lastMs = parseInt(props.getProperty('lastSyncMs') || '0', 10);
  var debounceMs = SCRIPT_CONFIG.debounceMinutes * 60 * 1000;
  if (Date.now() - lastMs < debounceMs) {
    return;
  }

  var version = String(sheet.getRange(SCRIPT_CONFIG.versionCell).getValue() || '').trim();
  if (!version) {
    version =
      'auto-' +
      Utilities.formatDate(new Date(), 'Asia/Jakarta', 'yyyyMMdd-HHmmss');
  }

  var ok = callDashboardSyncWebhook(version);
  if (ok) {
    props.setProperty('lastSyncMs', String(Date.now()));
    props.setProperty('lastSyncVersion', version);
  }
}

/**
 * Tombol manual di sheet: Extensions → Macros → import function triggerDashboardSyncManual
 */
function triggerDashboardSyncManual() {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(
    SCRIPT_CONFIG.dashboardSheetName
  );
  if (!sheet) {
    throw new Error('Tab Dashboard tidak ditemukan');
  }
  var version = String(sheet.getRange(SCRIPT_CONFIG.versionCell).getValue() || '').trim();
  if (!version) {
    version = 'manual-' + Utilities.formatDate(new Date(), 'Asia/Jakarta', 'yyyyMMdd-HHmmss');
  }
  var ok = callDashboardSyncWebhook(version);
  SpreadsheetApp.getUi().alert(ok ? 'Sync dijadwalkan: ' + version : 'Gagal memanggil webhook. Cek token & log.');
}

function callDashboardSyncWebhook(version) {
  if (!SCRIPT_CONFIG.webhookToken || SCRIPT_CONFIG.webhookToken.indexOf('ISI_') === 0) {
    Logger.log('webhookToken belum di-set');
    return false;
  }

  var payload = JSON.stringify({ version: version });
  var resp = UrlFetchApp.fetch(SCRIPT_CONFIG.laravelWebhookUrl, {
    method: 'post',
    contentType: 'application/json',
    headers: {
      Authorization: 'Bearer ' + SCRIPT_CONFIG.webhookToken,
      Accept: 'application/json',
    },
    payload: payload,
    muteHttpExceptions: true,
  });

  var code = resp.getResponseCode();
  Logger.log('Webhook response ' + code + ': ' + resp.getContentText());

  return code === 200 || code === 202;
}
