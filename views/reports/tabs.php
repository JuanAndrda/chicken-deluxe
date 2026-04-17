<!-- Report Navigation Tabs -->
<div class="report-tabs">
    <a href="<?= BASE_URL ?>/reports/daily"
       class="tab <?= ($active_tab ?? '') === 'daily' ? 'tab-active' : '' ?>">Daily Summary</a>
    <a href="<?= BASE_URL ?>/reports/consolidated"
       class="tab <?= ($active_tab ?? '') === 'consolidated' ? 'tab-active' : '' ?>">Consolidated</a>
    <a href="<?= BASE_URL ?>/reports/timein"
       class="tab <?= ($active_tab ?? '') === 'timein' ? 'tab-active' : '' ?>">Staff Time-In</a>
</div>
