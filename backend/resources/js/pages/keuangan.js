import Chart from 'chart.js/auto';

function formatRp(value) {
    return 'Rp ' + Number(value).toLocaleString('id-ID');
}

function expandPeriodLabel(value) {
    if (!value) return '';
    const parts = value.split('-');
    if (parts.length === 3) {
        const date = new Date(parts[0], parts[1] - 1, parts[2]);
        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    }
    if (value === '1w') return '1 Minggu Terakhir';
    if (value === '1m') return '1 Bulan Terakhir';
    if (value === 'all') return 'Semua Waktu';
    return '6 Bulan Terakhir';
}

function buildChartDefaults() {
    return {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { usePointStyle: true, padding: 4, boxWidth: 5, font: { family: 'Inter', size: 8 } }
            },
            tooltip: {
                backgroundColor: '#1e293b',
                titleFont: { family: 'Inter', size: 9 },
                bodyFont: { family: 'Inter', size: 9 },
                padding: 6,
                cornerRadius: 4,
                displayColors: true,
                boxPadding: 2,
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { family: 'Inter', size: 8 }, color: '#94a3b8', maxTicksLimit: 6 }
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                ticks: { font: { family: 'Inter', size: 8 }, color: '#94a3b8', maxTicksLimit: 4 }
            }
        }
    };
}

function defaultState(data) {
    return {
        period: data.period || '6m',
        month: data.month || null,
        expenseFilter: data.expenseFilter || 'all',
    };
}

let loadingTimer = null;
function setLoading() {
    // Loading indicator is disabled for the keuangan page.
}

function createCashFlowDataset(cashFlowData) {
    const labels = Object.keys(cashFlowData).sort();
    const pemasukan = labels.map((key) => {
        const group = cashFlowData[key] || [];
        const item = group.find((row) => row.tipe === 'Pemasukan');
        return item ? Number(item.total) : 0;
    });
    const pengeluaran = labels.map((key) => {
        const group = cashFlowData[key] || [];
        const item = group.find((row) => row.tipe === 'Pengeluaran');
        return item ? Number(item.total) : 0;
    });

    return { labels, pemasukan, pengeluaran };
}

function updateCashFlowChart(chart, cashFlowData) {
    const { labels, pemasukan, pengeluaran } = createCashFlowDataset(cashFlowData);
    chart.data.labels = labels.map(expandPeriodLabel);
    chart.data.datasets[0].data = pemasukan;
    chart.data.datasets[1].data = pengeluaran;
    chart.update();
}

function updateKantorPerMonthChart(chart, data) {
    const labels = data.map((item) => expandPeriodLabel(item.bulan));
    chart.data.labels = labels;
    chart.data.datasets[0].data = data.map((item) => Number(item.total));
    chart.update();
}

function updateFinancialPieChart(chart, report) {
    const labaRugiLabel = report.labaRugi >= 0 ? 'Laba Bersih' : 'Rugi Bersih';
    chart.data.labels = ['Total Pemasukan', 'Total Pengeluaran', labaRugiLabel];
    chart.data.datasets[0].data = [report.totalPemasukan, report.totalPengeluaran, Math.abs(report.labaRugi)];
    chart.update();
}

function updateFilterButtons(state) {
    document.querySelectorAll('.period-btn').forEach((button) => {
        const value = button.dataset.period;
        const isActive = value === state.period;
        button.classList.toggle('bg-white', isActive);
        button.classList.toggle('text-slate-900', isActive);
        button.classList.toggle('shadow-sm', isActive);
        button.classList.toggle('text-slate-500', !isActive);
        button.classList.toggle('hover:text-slate-800', !isActive);
    });
    document.querySelectorAll('.expense-btn').forEach((button) => {
        const value = button.dataset.expense;
        const isActive = value === state.expenseFilter;
        button.classList.toggle('bg-white', isActive);
        button.classList.toggle('text-slate-900', isActive);
        button.classList.toggle('shadow-sm', isActive);
        button.classList.toggle('text-slate-500', !isActive);
        button.classList.toggle('hover:text-slate-800', !isActive);
    });
}

function updatePageElements(report) {
    const text = {
        periodLabel: report.periodLabel,
        expenseFilterLabel: report.expenseFilterLabel,
        totalPemasukan: formatRp(report.totalPemasukan),
        totalPengeluaran: formatRp(report.totalPengeluaran),
        pengeluaranDesc: report.expenseFilter === 'all'
            ? 'Mencakup pengeluaran kantor & non-kantor dalam periode ini.'
            : report.expenseFilter === 'kantor'
                ? 'Hanya pengeluaran kantor dalam periode ini.'
                : 'Hanya pengeluaran non-kantor dalam periode ini.',
        pengeluaranKantor: formatRp(report.pengeluaranKantor),
        pengeluaranNonKantor: formatRp(report.pengeluaranNonKantor),
        labaRugi: formatRp(Math.abs(report.labaRugi)),
        labaRugiNote: report.labaRugi < 0 ? 'Rugi' : 'Laba',
        growthRate: `${report.growthRate >= 0 ? '+' : ''}${report.growthRate}%`,
        profitMargin: `${report.profitMargin}%`,
        labaBersih: formatRp(report.labaBersih),
        cashRunway: `${report.cashRunway} hari`,
    };

    const elements = {
        periodLabelBadge: document.getElementById('periodLabelBadge'),
        expenseFilterBadge: document.getElementById('expenseFilterBadge'),
        expenseFilterLabel: document.getElementById('expenseFilterLabel'),
        totalPemasukanValue: document.getElementById('totalPemasukanValue'),
        totalPengeluaranValue: document.getElementById('totalPengeluaranValue'),
        pengeluaranDesc: document.getElementById('pengeluaranDesc'),
        pengeluaranKantorValue: document.getElementById('pengeluaranKantorValue'),
        pengeluaranNonKantorValue: document.getElementById('pengeluaranNonKantorValue'),
        labaRugiValue: document.getElementById('labaRugiValue'),
        labaRugiBadge: document.getElementById('labaRugiBadge'),
        labaRugiFilterNote: document.getElementById('labaRugiFilterNote'),
        growthRateValue: document.getElementById('growthRateValue'),
        growthRateBadge: document.getElementById('growthRateBadge'),
        cashFlowPeriodLabel: document.getElementById('cashFlowPeriodLabel'),
        cashFlowFilterLabel: document.getElementById('cashFlowFilterLabel'),
        profitMarginValue: document.getElementById('profitMarginValue'),
        profitMarginFilterLabel: document.getElementById('profitMarginFilterLabel'),
        labaBersihValue: document.getElementById('labaBersihValue'),
        cashRunwayValue: document.getElementById('cashRunwayValue'),
    };

    if (elements.periodLabelBadge) elements.periodLabelBadge.textContent = text.periodLabel;
    if (elements.expenseFilterBadge) elements.expenseFilterBadge.textContent = text.expenseFilterLabel;
    if (elements.expenseFilterLabel) elements.expenseFilterLabel.textContent = text.expenseFilterLabel;
    if (elements.totalPemasukanValue) elements.totalPemasukanValue.textContent = text.totalPemasukan;
    if (elements.totalPengeluaranValue) elements.totalPengeluaranValue.textContent = text.totalPengeluaran;
    if (elements.pengeluaranDesc) elements.pengeluaranDesc.textContent = text.pengeluaranDesc;
    if (elements.pengeluaranKantorValue) elements.pengeluaranKantorValue.textContent = text.pengeluaranKantor;
    if (elements.pengeluaranNonKantorValue) elements.pengeluaranNonKantorValue.textContent = text.pengeluaranNonKantor;
    if (elements.labaRugiValue) elements.labaRugiValue.textContent = text.labaRugi;
    if (elements.labaRugiBadge) elements.labaRugiBadge.textContent = text.labaRugiNote;
    if (elements.labaRugiFilterNote) elements.labaRugiFilterNote.textContent = `Dihitung dari pengeluaran ${report.expenseFilterLabel.toLowerCase()} saja`;
    if (elements.growthRateValue) elements.growthRateValue.textContent = text.growthRate;
    if (elements.growthRateBadge) elements.growthRateBadge.textContent = report.growthRate >= 0 ? 'Naik' : 'Turun';
    if (elements.cashFlowPeriodLabel) elements.cashFlowPeriodLabel.textContent = `Periode ${text.periodLabel}`;
    if (elements.cashFlowFilterLabel) elements.cashFlowFilterLabel.textContent = report.expenseFilter !== 'all' ? report.expenseFilterLabel : '';
    if (elements.profitMarginValue) elements.profitMarginValue.textContent = text.profitMargin;
    if (elements.profitMarginFilterLabel) elements.profitMarginFilterLabel.textContent = report.expenseFilter !== 'all' ? report.expenseFilterLabel : '';
    if (elements.labaBersihValue) elements.labaBersihValue.textContent = text.labaBersih;
    if (elements.cashRunwayValue) elements.cashRunwayValue.textContent = text.cashRunway;
}

function buildHistoryUrl(url, state) {
    const params = new URLSearchParams();
    if (state.period) params.set('period', state.period);
    if (state.month) params.set('month', state.month);
    if (state.expenseFilter) params.set('expense_filter', state.expenseFilter);
    return `${url}?${params.toString()}`;
}

async function fetchReport(url, state) {
    const params = new URLSearchParams();
    if (state.period) params.set('period', state.period);
    if (state.month) params.set('month', state.month);
    if (state.expenseFilter) params.set('expense_filter', state.expenseFilter);

    const response = await fetch(`${url}?${params.toString()}`, {
        headers: { 'Accept': 'application/json' }
    });

    if (!response.ok) {
        throw new Error('Gagal mengambil data laporan');
    }

    return response.json();
}

function initCharts(initialData) {
    const cashFlowCtx = document.getElementById('cashFlowChart');
    const kantorCtx = document.getElementById('kantorPerMonthChart');
    const pieCtx = document.getElementById('financialPieChart');

    const baseDefaults = buildChartDefaults();
    const cashFlowDataset = createCashFlowDataset(initialData.cashFlow);

    const cashFlowChart = new Chart(cashFlowCtx, {
        type: 'line',
        data: {
            labels: cashFlowDataset.labels.map(expandPeriodLabel),
            datasets: [
                {
                    label: 'Pemasukan',
                    data: cashFlowDataset.pemasukan,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.12)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2
                },
                {
                    label: 'Pengeluaran',
                    data: cashFlowDataset.pengeluaran,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.12)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2
                }
            ]
        },
        options: {
            ...baseDefaults,
            plugins: {
                ...baseDefaults.plugins,
                tooltip: {
                    ...baseDefaults.plugins.tooltip,
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label}: ${formatRp(ctx.raw)}`
                    }
                }
            }
        }
    });

    const kantorChart = new Chart(kantorCtx, {
        type: 'bar',
        data: {
            labels: initialData.pengeluaranKantorPerMonth.map((item) => expandPeriodLabel(item.bulan)),
            datasets: [{
                label: 'Pengeluaran Kantor',
                data: initialData.pengeluaranKantorPerMonth.map((item) => Number(item.total)),
                backgroundColor: '#3b82f6',
                borderRadius: 4,
                barThickness: 18,
            }]
        },
        options: {
            ...baseDefaults,
            plugins: {
                ...baseDefaults.plugins,
                legend: { display: false },
                tooltip: {
                    ...baseDefaults.plugins.tooltip,
                    callbacks: {
                        label: (ctx) => formatRp(ctx.raw)
                    }
                }
            }
        }
    });

    const pieChart = new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: [
                'Total Pemasukan',
                'Total Pengeluaran',
                initialData.labaRugi >= 0 ? 'Laba Bersih' : 'Rugi Bersih'
            ],
            datasets: [{
                data: [initialData.totalPemasukan, initialData.totalPengeluaran, Math.abs(initialData.labaRugi)],
                backgroundColor: ['#22c55e', '#ef4444', '#2563eb'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '55%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, padding: 4, boxWidth: 5, font: { family: 'Inter', size: 8 } }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { family: 'Inter', size: 9 },
                    bodyFont: { family: 'Inter', size: 9 },
                    padding: 6,
                    cornerRadius: 4,
                    callbacks: {
                        label: (ctx) => `${ctx.label}: ${formatRp(ctx.raw)}`
                    }
                }
            }
        }
    });

    return { cashFlowChart, kantorChart, pieChart };
}

function updateUrlState(urlBase, state) {
    const newUrl = buildHistoryUrl(urlBase, state);
    window.history.pushState(state, '', newUrl);
}

async function loadReportAndUpdate(state, urls, charts) {
    setLoading(true);
    try {
        const report = await fetchReport(urls.data, state);
        updatePageElements(report);
        updateFilterButtons(state);
        updateCashFlowChart(charts.cashFlowChart, report.cashFlow);
        updateKantorPerMonthChart(charts.kantorChart, report.pengeluaranKantorPerMonth);
        updateFinancialPieChart(charts.pieChart, report);
        updateUrlState(urls.base, state);
    } finally {
        setLoading(false);
    }
}

export function initLaporanKeuangan() {
    const page = document.getElementById('laporanKeuanganPage');
    if (!page || !window.__laporan) return;

    const urls = window.__laporan.urls;
    const initialData = window.__laporan.data;
    const state = defaultState(initialData);

    const charts = initCharts(initialData);
    updatePageElements(initialData);
    updateFilterButtons(state);

    page.querySelectorAll('.period-btn').forEach((button) => {
        button.addEventListener('click', () => {
            const period = button.dataset.period;
            if (!period) return;
            state.period = period;
            state.month = null;
            loadReportAndUpdate(state, urls, charts);
        });
    });

    page.querySelectorAll('.expense-btn').forEach((button) => {
        button.addEventListener('click', () => {
            const expense = button.dataset.expense;
            if (!expense) return;
            state.expenseFilter = expense;
            loadReportAndUpdate(state, urls, charts);
        });
    });

    const monthInput = document.getElementById('monthInput');
    const btnLihatBulan = document.getElementById('btnLihatBulan');
    const btnResetBulan = document.getElementById('btnResetBulan');

    if (monthInput && btnLihatBulan) {
        btnLihatBulan.addEventListener('click', () => {
            state.month = monthInput.value || null;
            loadReportAndUpdate(state, urls, charts);
        });
    }

    if (btnResetBulan) {
        btnResetBulan.addEventListener('click', () => {
            state.month = null;
            if (monthInput) monthInput.value = '';
            loadReportAndUpdate(state, urls, charts);
        });
    }

    window.addEventListener('popstate', (event) => {
        if (!event.state) return;
        state.period = event.state.period || '6m';
        state.month = event.state.month || null;
        state.expenseFilter = event.state.expenseFilter || 'all';
        if (monthInput) monthInput.value = state.month || '';
        loadReportAndUpdate(state, urls, charts);
    });
}

document.addEventListener('DOMContentLoaded', initLaporanKeuangan);
