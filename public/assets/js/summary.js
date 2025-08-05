// เพิ่มฟังก์ชัน toggleSummaryModal ไว้ด้านบนไฟล์
function toggleSummaryModal(show) {
    const modal = document.querySelector('#summaryModal');
    if (!modal) return;

    if (show) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    } else {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // เพิ่มการตรวจสอบว่ามีปุ่มหรือไม่
    const summaryButton = document.querySelector('#summaryButton');
    if (!summaryButton) {
        console.error('ไม่พบปุ่ม Summary');
        return;
    }

    summaryButton.addEventListener('click', async function () {
        try {
            showLoading();
            const response = await fetch('api/summary.php');
            const result = await response.json();

            if (result.success) {
                updateModalContent(result.data);
                // แก้ไขการแสดง modal
                toggleSummaryModal(true);
            } else {
                console.error('เกิดข้อผิดพลาด:', result.message);
            }
        } catch (error) {
            console.error('เกิดข้อผิดพลาดในการโหลดข้อมูล:', error);
        } finally {
            hideLoading();
        }
    });

    // แก้ไขส่วน close buttons ให้ใช้ toggleSummaryModal
    const closeButtons = document.querySelectorAll('[data-dismiss="modal"]');
    closeButtons.forEach(button => {
        button.addEventListener('click', () => toggleSummaryModal(false));
    });

    function updateModalContent(data) {
        // อัปเดตข้อมูลทั่วไป
        document.getElementById('summary-total').textContent = data.total || 0;
        document.getElementById('summary-other-risks').textContent = data.other_risks || 0;

        // อัปเดต General risks
        const generalContainer = document.getElementById('riskContainer');
        if (generalContainer && data.risk_counts?.general_risks) {
            generalContainer.innerHTML = '';
            Object.entries(data.risk_counts.general_risks)
                .sort((a, b) => b[1] - a[1])
                .forEach(([risk, count]) => {
                    generalContainer.innerHTML += `
                        <div class="bg-white p-2 rounded shadow flex flex-col justify-center h-14 text-center hover:shadow-md transition-shadow">
                            <div class="font-semibold text-sm mb-1 truncate">${risk}</div>
                            <div class="text-xs font-bold text-rose-600">${count} คน</div>
                        </div>
                    `;
                });
        }

        // อัปเดต MED risks
        const medContainer = document.getElementById('riskMedicalContainer');
        if (medContainer && data.risk_counts?.med_risks) {
            medContainer.innerHTML = '';
            Object.entries(data.risk_counts.med_risks)
                .sort((a, b) => b[1] - a[1])
                .forEach(([risk, count]) => {
                    const riskName = risk.replace('MED : ', '');
                    medContainer.innerHTML += `
                        <div class="bg-white p-2 rounded shadow flex flex-col justify-center h-14 text-center hover:shadow-md transition-shadow">
                            <div class="font-semibold text-sm mb-1 truncate">${riskName}</div>
                            <div class="text-xs font-bold text-rose-600">${count} คน</div>
                        </div>
                    `;
                });
        }

        // อัปเดต OBS risks
        const obsContainer = document.getElementById('riskObstetricContainer');
        if (obsContainer && data.risk_counts?.obs_risks) {
            obsContainer.innerHTML = '';
            Object.entries(data.risk_counts.obs_risks)
                .sort((a, b) => b[1] - a[1])
                .forEach(([risk, count]) => {
                    const riskName = risk.replace('OBS : ', '');
                    obsContainer.innerHTML += `
                        <div class="bg-white p-2 rounded shadow flex flex-col justify-center h-14 text-center hover:shadow-md transition-shadow">
                            <div class="font-semibold text-sm mb-1 truncate">${riskName}</div>
                            <div class="text-xs font-bold text-rose-600">${count} คน</div>
                        </div>
                    `;
                });
        }

        // อัปเดตข้อมูลอื่นๆ คงเดิม
        if (data.delivery) {
            document.getElementById('summary-delivery-banthi').textContent = data.delivery.banthi || 0;
            document.getElementById('summary-delivery-lamphun').textContent = data.delivery.lamphun || 0;
            document.getElementById('summary-delivery-others').textContent = data.delivery.others || 0;
        }

        if (data.hct) {
            document.getElementById('summary-hct-low').textContent = data.hct.low_hct || 0;
            document.getElementById('summary-hct-mid').textContent = data.hct.mid_hct || 0;
            document.getElementById('summary-hct-high').textContent = data.hct.high_hct || 0;
        }

        // --- แก้ไขตรงนี้ ---
        // const riskLabels = ['ความเสี่ยงทั่วไป', 'อายุรกรรม', 'สูติกรรม'];
        // const riskData = [
        //     Object.keys(data.risk_counts.general_risks).length,
        //     Object.keys(data.risk_counts.med_risks).length,
        //     Object.keys(data.risk_counts.obs_risks).length
        // ];

        // เปลี่ยนเป็นนับจำนวนคน
        const riskLabels = ['ความเสี่ยงทั่วไป', 'อายุรกรรม', 'สูติกรรม'];
        const riskData = [
            Object.values(data.risk_counts.general_risks).reduce((a, b) => a + b, 0),
            Object.values(data.risk_counts.med_risks).reduce((a, b) => a + b, 0),
            Object.values(data.risk_counts.obs_risks).reduce((a, b) => a + b, 0)
        ];

        renderRiskChart(
            data.risk_counts.general_risks,
            data.risk_counts.med_risks,
            data.risk_counts.obs_risks
        );
    }

    // ดาวน์โหลดรายงาน
    const downloadBtn = document.getElementById('downloadSummary');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function () {
            window.location.href = 'api/export_summary.php';
        });
    }
});

// เพิ่มฟังก์ชัน Loading
function showLoading() {
    const loader = document.querySelector('#loadingSpinner');
    if (loader) loader.classList.remove('hidden');
}

function hideLoading() {
    const loader = document.querySelector('#loadingSpinner');
    if (loader) loader.classList.add('hidden');
}

function renderRiskChart(generalRisks, medRisks, obstetricRisks) {
    // Destroy old charts if exist
    if (window.riskChartGeneral && typeof window.riskChartGeneral.destroy === 'function') window.riskChartGeneral.destroy();
    if (window.riskChartMedical && typeof window.riskChartMedical.destroy === 'function') window.riskChartMedical.destroy();
    if (window.riskChartObstetric && typeof window.riskChartObstetric.destroy === 'function') window.riskChartObstetric.destroy();

    // Helper for each chart
    function drawChart(canvasId, risks, colors, labelColor) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        const labels = Object.keys(risks);
        const dataArr = Object.values(risks);
        if (labels.length === 0 || dataArr.every(v => v === 0)) return;

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: dataArr,
                    backgroundColor: colors,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }, // ซ่อน legend
                    datalabels: {
                        color: labelColor,
                        font: { weight: 'bold', size: 14 },
                        formatter: function (value, ctx) {
                            return value > 0 ? value + ' คน' : '';
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    // สร้างสีสุ่มสำหรับแต่ละ risk
    function genColors(n, base) {
        // ขยาย palette ให้มากขึ้นเพื่อรองรับ risk จำนวนมาก
        const palette = base || [
            '#f87171', '#fbbf24', '#60a5fa', '#34d399', '#a78bfa', '#f472b6', '#facc15', '#38bdf8', '#fb7185', '#a3e635',
            '#f59e42', '#f472b6', '#facc15', '#818cf8', '#a5b4fc', '#0ea5e9', '#fca5a5', '#fecaca', '#991b1b', '#fb7185',
            '#f43f5e', '#e11d48', '#fde68a', '#b91c1c', '#dc2626', '#ef4444', '#fbbf24', '#f59e42', '#a3e635', '#34d399', '#60a5fa'
        ];
        let arr = [];
        for (let i = 0; i < n; i++) arr.push(palette[i % palette.length]);
        return arr;
    }

    window.riskChartGeneral = drawChart(
        'riskChartGeneral',
        generalRisks,
        [
            '#B91C1C', '#DC2626', '#EF4444', '#F87171', '#FCA5A5', '#FECACA', '#991B1B',
            '#FB7185', '#F43F5E', '#E11D48', '#F87171', '#FCA5A5', '#FECACA', '#FB7185'
        ],
        '#222' 
    );
    window.riskChartMedical = drawChart(
        'riskChartMedical',
        medRisks,
        [
            '#F59E42', '#FBBF24', '#FDE68A', '#FACC15', '#FCD34D', '#FDE047', '#FDE68A',
            '#FBBF24', '#F59E42', '#FDE68A', '#FACC15', '#FCD34D', '#FDE047', '#FDE68A',
            '#FBBF24', '#F59E42', '#FDE68A'
        ],
        '#7c4700' 
    );
    window.riskChartObstetric = drawChart(
        'riskChartObstetric',
        obstetricRisks,
        [
            '#1E40AF', '#1D4ED8', '#2563EB', '#3B82F6', '#60A5FA', '#93C5FD', '#BFDBFE',
            '#0EA5E9', '#38BDF8', '#7DD3FC', '#0284C7', '#0369A1', '#0EA5E9', '#38BDF8',
            '#7DD3FC', '#0284C7', '#0369A1'
        ],
        '#0a2540' 
    );
}
