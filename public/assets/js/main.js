// Global state
const state = {
    currentPage: 1,
    itemsPerPage: 10,
    filter: 'all',
    totalRecords: 0
};

// Main initialization
document.addEventListener('DOMContentLoaded', () => {
    initializeEventListeners();
    setupModalEvents();

    // ประกาศตัวแปรครั้งเดียวที่จุดเริ่มต้น
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const entriesPerPage = document.getElementById('entriesPerPage');

    // ฟังก์ชันสำหรับโหลดข้อมูลใหม่
    function loadData(page = 1) {
        const search = document.getElementById('searchInput').value.trim();
        const filter = document.getElementById('statusFilter').value;
        const limit = document.getElementById('entriesPerPage').value;

        const params = new URLSearchParams({
            search,
            filter,
            limit,
            page
        });

        fetch(`home.php?${params.toString()}`)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const newDocument = parser.parseFromString(html, 'text/html');

                // อัปเดตตาราง
                const tableContainer = document.querySelector('.table-container');
                const newTable = newDocument.querySelector('.table-container table');
                if (tableContainer && newTable) {
                    tableContainer.innerHTML = newTable.outerHTML;
                    setupModalEvents();
                }

                // อัปเดต pagination
                const paginationContainer = document.querySelector('nav[aria-label="Pagination"]');
                const newPagination = newDocument.querySelector('nav[aria-label="Pagination"]');
                if (paginationContainer && newPagination) {
                    paginationContainer.innerHTML = newPagination.innerHTML;
                }

                // อัปเดตจำนวนรายการ
                const startEntry = newDocument.getElementById('startEntry');
                const endEntry = newDocument.getElementById('endEntry');
                const totalEntries = newDocument.getElementById('totalEntries');
                if (startEntry && endEntry && totalEntries) {
                    document.getElementById('startEntry').textContent = startEntry.textContent;
                    document.getElementById('endEntry').textContent = endEntry.textContent;
                    document.getElementById('totalEntries').textContent = totalEntries.textContent;
                }
            })
            .catch(error => console.error('Error loading data:', error));
    }

    // Event Listeners
    searchInput.addEventListener('input', debounce(() => loadData(1), 300)); // ค้นหาเมื่อพิมพ์
    statusFilter.addEventListener('change', () => loadData(1)); // กรองข้อมูลเมื่อเปลี่ยนสถานะ
    entriesPerPage.addEventListener('change', () => loadData(1)); // เปลี่ยนจำนวนรายการต่อหน้า

    // Event Listener สำหรับ Pagination
    document.addEventListener('click', (event) => {
        const btn = event.target.closest('button[data-page]');
        if (btn && !btn.disabled) {
            const page = parseInt(btn.getAttribute('data-page'));
            if (!isNaN(page)) {
                loadData(page);
            }
        }
    });

    document.getElementById('clearSearch').addEventListener('click', () => {
        document.getElementById('searchInput').value = '';
        loadData(1);
    });

    // Event Listener สำหรับช่องค้นหา
    if (searchInput) {
        // ค้นหาเมื่อพิมพ์
        searchInput.addEventListener('input', debounce(() => {
            const searchValue = searchInput.value.trim();
            console.log('Search value:', searchValue); // Debug log

            const params = new URLSearchParams({
                search: searchValue,
                filter: statusFilter.value,
                page: 1,
                limit: entriesPerPage?.value || 10
            });

            console.log('Request URL:', `home.php?${params.toString()}`); // Debug log

            fetch(`home.php?${params.toString()}`)
                .then(response => {
                    console.log('Response status:', response.status); // Debug log
                    return response.text();
                })
                .then(html => {
                    try {
                        // ตรวจสอบว่าเป็น JSON error หรือไม่
                        const data = JSON.parse(html);
                        if (data.error) {
                            console.error('Server error:', data);
                            return;
                        }
                    } catch (e) {
                        // ถ้าไม่ใช่ JSON ให้ดำเนินการต่อตามปกติ
                        const parser = new DOMParser();
                        const newDocument = parser.parseFromString(html, 'text/html');

                        // อัปเดตตารางข้อมูล
                        const tableContainer = document.querySelector('.table-container');
                        const newTable = newDocument.querySelector('.table-container table');
                        if (tableContainer && newTable) {
                            tableContainer.innerHTML = newTable.outerHTML;
                            setupModalEvents();
                        }

                        // อัปเดต Pagination
                        const paginationContainer = document.getElementById('pageNumbers');
                        const newPagination = newDocument.getElementById('pageNumbers');
                        if (paginationContainer && newPagination) {
                            paginationContainer.innerHTML = newPagination.innerHTML;
                        }

                        // อัปเดตข้อมูลการแสดงรายการ
                        const startEntry = newDocument.getElementById('startEntry');
                        const endEntry = newDocument.getElementById('endEntry');
                        const totalEntries = newDocument.getElementById('totalEntries');
                        if (startEntry && endEntry && totalEntries) {
                            document.getElementById('startEntry').textContent = startEntry.textContent;
                            document.getElementById('endEntry').textContent = endEntry.textContent;
                            document.getElementById('totalEntries').textContent = totalEntries.textContent;
                        }
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
        }, 300));
    }

    // Event Listener สำหรับปุ่ม Clear Search
    const clearButton = document.getElementById('clearSearch');
    if (clearButton) {
        clearButton.addEventListener('click', () => {
            if (searchInput) {
                searchInput.value = '';
                // ทำการค้นหาใหม่หลังจากล้างค่า
                searchInput.dispatchEvent(new Event('input'));
            }
        });
    }
});

function initializeEventListeners() {
    // Filter buttons
    document.getElementById('todayButton')?.addEventListener('click', () => {
        // ใช้ replaceState เพื่อลบพารามิเตอร์ออกจาก URL
        window.history.replaceState({}, '', 'home.php');
        
        fetch('home.php?filter=today')
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const newDocument = parser.parseFromString(html, 'text/html');

                // อัพเดตตาราง
                const tableContainer = document.querySelector('.table-container');
                const newTable = newDocument.querySelector('.table-container table');
                if (tableContainer && newTable) {
                    tableContainer.innerHTML = newTable.outerHTML;
                    setupModalEvents();
                }
            })
            .catch(error => console.error('Error:', error));
    });

    document.getElementById('allListButton')?.addEventListener('click', () => {
        loadData('all');
    });

    // New Data Modal
    document.getElementById('openExampleModal')?.addEventListener('click', () => {
        openModal('exampleModal');
    });

    document.getElementById('closeExampleModal')?.addEventListener('click', () => {
        closeModal('exampleModal');
    });

    document.getElementById('cancelExampleModal')?.addEventListener('click', () => {
        closeModal('exampleModal');
    });

}

// Modal Management
function setupModalEvents() {
    const elements = {
        modal: document.getElementById('showModal'),
        closeBtn: document.getElementById('closeShow'),
        cancelBtn: document.getElementById('cancelShowModal'),
        editBtn: document.getElementById('editData'),
        openBtns: document.querySelectorAll('.openModal'),
        idInput: document.getElementById('modal-id')
    };

    if (!validateModalElements(elements)) return;

    elements.openBtns.forEach(btn => {
        btn.addEventListener('click', () => handleModalOpen(btn, elements));
    });

    elements.closeBtn?.addEventListener('click', () =>
        toggleModal(elements.modal, false));

    elements.cancelBtn?.addEventListener('click', () =>
        toggleModal(elements.modal, false));

    elements.editBtn?.addEventListener('click', () => {
        // ค้นหา form ในโมดอล
        const form = document.querySelector('#showModal form');
        if (form) {
            // Log data before submit
            const formData = new FormData(form);
            console.log('Submitting form data:', Object.fromEntries(formData));

            // Submit form
            form.submit();
        } else {
            console.error('Form not found in modal');
        }
    });
}

function validateModalElements(elements) {
    if (!elements.modal || !elements.idInput) {
        console.error('Required modal elements not found');
        return false;
    }
    return true;
}

function handleModalOpen(button, elements) {
    const id = button.getAttribute('data-id');
    if (!id) {
        console.error('Invalid data-id');
        return;
    }

    elements.idInput.value = id;
    fetchAndPopulateModal(id, elements.modal);
}

function fetchAndPopulateModal(id, modal) {
    fetch(`api/get_data.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            populateModalFields(data);
            toggleModal(modal, true);
        })
        .catch(error => console.error('Error fetching modal data:', error));
}

// function populateRiskFields(data) {
//     ['risk1', 'risk2', 'risk3'].forEach(riskField => {
//         const selectElement = document.getElementById(`modal-${riskField}`);
//         if (selectElement) {
//             const riskId = String(data[riskField] || '');

//             // หาและเลือก option ที่ตรงกับ riskId
//             Array.from(selectElement.options).forEach(option => {
//                 if (option.value === riskId) {
//                     option.selected = true;
//                 }
//             });

//             // ถ้าไม่พบ option ที่ตรงกัน ให้เลือก "No Risk"
//             if (!selectElement.value) {
//                 selectElement.value = '';
//             }
//         }
//     });
// }

// เพิ่มฟังก์ชันนี้เข้าไปใน populateModalFields
function populateModalFields(data) {
    // จัดการ risk4
    const risk4Element = document.getElementById('modal-risk4');
    if (risk4Element) {
        risk4Element.value = data.risk4 || '';
    }

    // จัดการ fields อื่นๆ ที่มีอยู่เดิม
    const fields = [
        'place_of_antenatal', 'hn', 'cid', 'age', 'fullname', 'first_antenatal_date',
        'pregnancy_number', 'lmp', 'ga', 'edc_us', 'lap_alert',
        'hct_last', 'hct_date',
        'status', 'delivery_plan', 'special_drug',
        'delivery_date_time', 'delivery_place', 'baby_hn', 'baby_gender',
        'baby_weight', 'postpartum_info', 'notes', 'phone_number' // <-- เพิ่มตรงนี้
    ];

    fields.forEach(field => {
        const element = document.getElementById(`modal-${field}`);
        if (element) {
            element.value = data[field] || '';
        }
    });

    // เพิ่มตรงนี้! หลังจาก set ค่า pregnancy_number
    if (data.pregnancy_number) {
        fillPregnancyFieldsFromNumber(data.pregnancy_number);
    }

    // เพิ่มตรงนี้! หลังจากเติมค่า hct_last
    const hctShow = document.getElementById('modal-hct_last');
    if (hctShow) setHctBg(hctShow);

    // GeneralRisks
    const checkedGeneral = data.risk ? data.risk.split(',') : [];
    document.querySelectorAll('#showModal input[name="risk[]"]').forEach(cb => {
        cb.checked = checkedGeneral.includes(cb.value);
    });

    // MedicalRisks
    const checkedMedical = data.risk_medical ? data.risk_medical.split(',') : [];
    document.querySelectorAll('#showModal input[name="risk_medical[]"]').forEach(cb => {
        cb.checked = checkedMedical.includes(cb.value);
    });

    // ObstetricRisks
    const checkedObstetric = data.risk_obstetric ? data.risk_obstetric.split(',') : [];
    document.querySelectorAll('#showModal input[name="risk_obstetric[]"]').forEach(cb => {
        cb.checked = checkedObstetric.includes(cb.value);
    });

    // Depression Screening 2Q
    if (typeof data.depression_q1 !== 'undefined') {
        document.querySelectorAll('input[name="depression_q1"]').forEach(el => {
            el.checked = (el.value == data.depression_q1);
        });
    }
    if (typeof data.depression_q2 !== 'undefined') {
        document.querySelectorAll('input[name="depression_q2"]').forEach(el => {
            el.checked = (el.value == data.depression_q2);
        });
    }
    // เรียกฟังก์ชันอัปเดตผลคัดกรอง (ถ้ามี)
    if (typeof checkDepressionScreening === 'function') checkDepressionScreening();

    // Populate risk checklist
    populateRiskChecklist(data.risk);

    // เพิ่มการอัปเดตการแสดงผลความเสี่ยงใน textarea
    updateRiskDisplay('risk[]', 'selectedGeneralRisksDisplay-showModal', 'ยังไม่ได้เลือกความเสี่ยงทั่วไป');
    updateRiskDisplay('risk_medical[]', 'selectedMedicalRisksDisplay-showModal', 'ยังไม่ได้เลือกความเสี่ยงทางอายุรกรรม');
    updateRiskDisplay('risk_obstetric[]', 'selectedObstetricRisksDisplay-showModal', 'ยังไม่ได้เลือกความเสี่ยงทางสูติกรรม');
}

function populateRiskChecklist(riskString) {
    const checkedIds = riskString ? riskString.split(',') : [];
    document.querySelectorAll('#showModal input[name="risk[]"]').forEach(cb => {
        cb.checked = checkedIds.includes(cb.value);
    });
}

// Data Management
function loadData(filter = 'all', search = '') {
    state.filter = filter;
    state.search = search;
    showSpinner(true);

    const params = new URLSearchParams({
        filter: state.filter,
        page: state.currentPage,
        limit: state.itemsPerPage,
        search: state.search
    });

    fetch(`home.php?${params.toString()}`)
        .then(response => response.text())
        .then(html => {
            const tableContainer = document.querySelector('.table-container');
            if (!tableContainer) {
                console.error('Table container not found');
                return;
            }

            // ล้างข้อมูลเก่าใน table-container
            tableContainer.innerHTML = '';

            // เพิ่มข้อมูลใหม่ที่โหลดมา
            const parser = new DOMParser();
            const newDocument = parser.parseFromString(html, 'text/html');
            const newTable = newDocument.querySelector('.table-container table');
            if (newTable) {
                tableContainer.appendChild(newTable);
            } else {
                console.error('Failed to load table content');
            }

            // ตั้งค่า modal ใหม่หลังจากโหลดข้อมูล
            setupModalEvents();

            // อัปเดต Pagination
            const paginationContainer = document.getElementById('pagination');
            const newPagination = newDocument.getElementById('pagination');
            if (paginationContainer && newPagination) {
                paginationContainer.innerHTML = newPagination.innerHTML;
                setupPagination();
            }
        })
        .catch(error => {
            console.error('Error loading data:', error);
        })
        .finally(() => {
            showSpinner(false);
        });
}

// updateFilterButtons
function updateFilterButtons(activeFilter) {
    const buttons = {
        today: document.getElementById('todayButton'),
        all: document.getElementById('allListButton')
    };
}

function getFilterName(filter) {
    switch (filter) {
        case 'today': return 'วันนี้';
        case 'new': return 'ใหม่';
        default: return 'ทั้งหมด';
    }
}

function showSpinner(show) {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.style.display = show ? 'flex' : 'none';
    }
}

function updateLastUpdate() {
    const lastUpdate = document.getElementById('lastUpdate');
    if (lastUpdate) {
        lastUpdate.textContent = new Date().toLocaleString('th-TH');
    }
}

function toggleFilterButtons(disabled) {
    ['todayButton', 'allListButton', 'newDataButton', 'openExampleModal'].forEach(id => {
        const button = document.getElementById(id);
        if (button) {
            button.disabled = disabled;
            button.classList.toggle('opacity-50', disabled);
        }
    });
}

function updateFilterButtons(activeFilter) {
    const buttons = {
        today: document.getElementById('todayButton'),
        all: document.getElementById('allListButton')
    };
}

// Utility Functions
function toggleModal(modal, show) {
    modal.classList.toggle('hidden', !show);
    modal.classList.toggle('flex', show);
}

function debounce(func, wait) {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function updateUrlState() {
    const params = new URLSearchParams();
    if (state.currentPage !== 1) params.set('page', state.currentPage);
    if (state.itemsPerPage !== 10) params.set('limit', state.itemsPerPage);
    if (state.filter !== 'all') params.set('filter', state.filter);

    const newUrl = params.toString() ? `?${params.toString()}` : window.location.pathname;
    history.replaceState(null, '', newUrl);
}

// Table Filtering
function filterTable() {
    const searchText = document.getElementById('searchInput')?.value || '';
    const statusFilter = document.getElementById('statusFilter')?.value || '';
    state.currentPage = 1; // รีเซ็ตไปที่หน้าแรกเมื่อมีการค้นหาใหม่
    loadData(statusFilter, searchText);
}

function handleRefresh() {
    const button = document.getElementById('refreshData');
    if (!button) return;

    button.disabled = true;
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังโหลด...';

    loadData().finally(() => {
        button.disabled = false;
        button.innerHTML = originalContent;
        document.getElementById('lastUpdate').textContent =
            new Date().toLocaleString('th-TH');
    });
}

function setupPagination() {
    const totalPages = Math.ceil(state.totalRecords / state.itemsPerPage);
    const paginationContainer = document.getElementById('pagination');

    if (!paginationContainer) return;

    paginationContainer.innerHTML = createPaginationButtons(totalPages);
    attachPaginationEventListeners(totalPages);
}

function attachPaginationEventListeners(totalPages) {
    const prevButton = document.getElementById('prevPage');
    const nextButton = document.getElementById('nextPage');

    prevButton?.addEventListener('click', () => {
        if (state.currentPage > 1) {
            state.currentPage--;
            filterTable();
        }
    });

    nextButton?.addEventListener('click', () => {
        if (state.currentPage < totalPages) {
            state.currentPage++;
            filterTable();
        }
    });

    // Add click events for page numbers
    document.querySelectorAll('.page-number').forEach(button => {
        button.addEventListener('click', (e) => {
            const page = parseInt(e.target.dataset.page);
            if (page !== state.currentPage) {
                state.currentPage = page;
                filterTable();
            }
        });
    });
}

function attachPaginationEventListeners(totalPages) {
    const prevButton = document.getElementById('prevPage');
    const nextButton = document.getElementById('nextPage');

    prevButton?.addEventListener('click', () => {
        if (state.currentPage > 1) {
            goToPage(state.currentPage - 1);
        }
    });

    nextButton?.addEventListener('click', () => {
        if (state.currentPage < totalPages) {
            goToPage(state.currentPage + 1);
        }
    });

    // Add click events for page numbers
    document.querySelectorAll('.page-number').forEach(button => {
        button.addEventListener('click', (e) => {
            goToPage(parseInt(e.target.dataset.page));
        });
    });
}

function createPaginationButtons(totalPages) {
    let buttons = [];
    const maxVisiblePages = 5;
    let startPage = Math.max(1, state.currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    // First page
    if (startPage > 1) {
        buttons.push(`<button class="page-number px-3 py-1 border rounded-lg" data-page="1">1</button>`);
        if (startPage > 2) buttons.push('<span class="px-2">...</span>');
    }

    // Page numbers
    for (let i = startPage; i <= endPage; i++) {
        buttons.push(`
            <button class="page-number px-3 py-1 border rounded-lg 
                ${state.currentPage === i ? 'bg-blue-500 text-white' : 'hover:bg-gray-100'}"
                data-page="${i}">
                ${i}
            </button>
        `);
    }

    // Last page
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) buttons.push('<span class="px-2">...</span>');
        buttons.push(`
            <button class="page-number px-3 py-1 border rounded-lg" 
                data-page="${totalPages}">
                ${totalPages}
            </button>
        `);
    }

    return `
        <button id="prevPage" class="px-3 py-1 border rounded-lg" 
                ${state.currentPage === 1 ? 'disabled' : ''}>
            ก่อนหน้า
        </button>
        <div id="pageNumbers" class="flex items-center space-x-1">
            ${buttons.join('')}
        </div>
        <button id="nextPage" class="px-3 py-1 border rounded-lg"
                ${state.currentPage === totalPages ? 'disabled' : ''}>
            ถัดไป
        </button>
    `;
}

function goToPage(page) {
    if (page < 1 || page > Math.ceil(state.totalRecords / state.itemsPerPage)) {
        console.warn('Invalid page number');
        return;
    }
    state.currentPage = page;
    loadData();
}

function updateDisplayInfo() {
    const start = Math.min(((state.currentPage - 1) * state.itemsPerPage) + 1, state.totalRecords);
    const end = Math.min(state.currentPage * state.itemsPerPage, state.totalRecords);

    const elements = {
        startEntry: document.getElementById('startEntry'),
        endEntry: document.getElementById('endEntry'),
        totalEntries: document.getElementById('totalEntries'),
        pageInfo: document.getElementById('pageInfo')
    };

    if (elements.startEntry) elements.startEntry.textContent = start;
    if (elements.endEntry) elements.endEntry.textContent = end;
    if (elements.totalEntries) elements.totalEntries.textContent = state.totalRecords;
    if (elements.pageInfo) {
        elements.pageInfo.textContent =
            `หน้า ${state.currentPage} จาก ${Math.ceil(state.totalRecords / state.itemsPerPage)}`;
    }
}

function setupAlertMessages() {
    const alertContainer = document.getElementById('alert-container');
    if (alertContainer?.children.length > 0) {
        const alertMessage = alertContainer.querySelector('.alert-message');
        if (alertMessage) {
            setTimeout(() => {
                alertMessage.style.opacity = '0';
                alertMessage.style.transform = 'translateY(-20px)';
                setTimeout(() => alertMessage.remove(), 300);
            }, 5000);
        }
    }
}

function updateActiveFilters() {
    const filterCount = [
        document.getElementById('statusFilter')?.value,
        document.getElementById('searchInput')?.value
    ].filter(Boolean).length;

    const filterLabel = document.getElementById('activeFilters');
    if (filterLabel) {
        filterLabel.textContent = filterCount > 0 ? `(${filterCount} ฟิลเตอร์)` : '';
    }
}

function updateFilterResults(visibleCount) {
    const resultLabel = document.getElementById('filterResults');
    const totalLabel = document.getElementById('totalRecords');

    if (resultLabel) {
        resultLabel.textContent = `พบ ${visibleCount} รายการ`;
    }
    if (totalLabel) {
        totalLabel.textContent = `จากทั้งหมด ${state.totalRecords} รายการ`;
    }
}

function showAlert(message, type = 'success') {
    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) return;

    const alertElement = document.createElement('div');
    alertElement.className = `alert-message ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} 
        text-white px-6 py-3 rounded-lg shadow-lg transition-all duration-300`;

    alertElement.innerHTML = `
        <div class="flex items-center gap-2 mb-1">
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
            <span>${message}</span>
        </div>
        <button type="button" class="absolute top-2 right-2 text-white hover:text-gray-200" 
                onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

    alertContainer.appendChild(alertElement);

    setTimeout(() => {
        alertElement.style.opacity = '0';
        alertElement.style.transform = 'translateY(-20px)';
        setTimeout(() => alertElement.remove(), 300);
    }, 5000);
}

function getAlertColor(type) {
    switch (type) {
        case 'success': return 'bg-green-100 text-green-800';
        case 'error': return 'bg-red-100 text-red-800';
        case 'warning': return 'bg-yellow-100 text-yellow-800';
        default: return 'bg-blue-100 text-blue-800';
    }
}

function initializeFilterButtons() {
    // ปุ่มกรอง Today
    document.getElementById('todayButton')?.addEventListener('click', () => {
        updateFilterButtons('today');
    });

    // ปุ่มแสดงทั้งหมด
    document.getElementById('allListButton')?.addEventListener('click', () => {
        updateFilterButtons('all');
    });

    // ปุ่มเพิ่มข้อมูลใหม่
    document.getElementById('openExampleModal')?.addEventListener('click', () => {
        openModal('exampleModal');
    });

    // ปุ่มปิด Modal
    document.getElementById('closeExampleModal')?.addEventListener('click', () => {
        closeModal('exampleModal');
    });
}

// เพิ่มฟังก์ชันเปิด-ปิด Modal
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.classList.remove('hidden');
    const modalContent = modal.querySelector('.bg-white');
    if (modalContent) {
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    // เพิ่ม event listener สำหรับการคลิกพื้นหลังเพื่อปิด modal
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModal(modalId);
        }
    });
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    const modalContent = modal.querySelector('.bg-white');
    if (modalContent) {
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
    }

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

function updateEntryInfo(page, limit, total) {
    const startEntry = ((page - 1) * limit) + 1;
    const endEntry = Math.min(page * limit, total);

    document.getElementById('startEntry').textContent = startEntry;
    document.getElementById('endEntry').textContent = endEntry;
    document.getElementById('totalEntries').textContent = total;
}

document.addEventListener('DOMContentLoaded', function () {
    const alertMessage = document.getElementById('alert-message');
    if (alertMessage) {
        setTimeout(() => {
            alertMessage.classList.add('fade-out');
            setTimeout(() => alertMessage.remove(), 5000); // ลบออกจาก DOM หลังจากเอฟเฟกต์จบ
        }, 5000); // 5 วินาที
    }
});

// เปิด Loading
function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
}

// ปิด Loading
function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

// ตัวอย่างการใช้งาน
async function fetchData() {
    try {
        showLoading(); // แสดง loading ก่อนโหลดข้อมูล

        // จำลองการโหลดข้อมูล
        await fetch('/api/data');

    } catch (error) {
        console.error('Error:', error);
    } finally {
        hideLoading(); // ซ่อน loading เมื่อโหลดเสร็จหรือมีข้อผิดพลาด
    }
}

// เพิ่ม Event Listener สำหรับปุ่มรีเฟรช
document.getElementById('refreshButton').addEventListener('click', function () {
    // แสดง loading spinner
    Swal.fire({
        title: 'กำลังรีเฟรชข้อมูล...',
        text: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // รีเฟรชหน้าเว็บ
    setTimeout(() => {
        window.location.reload();
    }, 1000);
});

// Session timeout warning
function setupSessionTimeout() {
    const warningTime = 25 * 60 * 1000; // 25 นาที
    const redirectTime = 30 * 60 * 1000; // 30 นาที

    setTimeout(() => {
        Swal.fire({
            title: 'แจ้งเตือน!',
            text: 'Session จะหมดอายุใน 5 นาที เตรียมตัวเข้าสู่ระบบใหม่',
            icon: 'warning',
            confirmButtonText: 'เข้าใจแล้ว'
        });
    }, warningTime);

    setTimeout(() => {
        Swal.fire({
            title: 'Session หมดอายุ',
            text: 'กรุณาเข้าสู่ระบบใหม่',
            icon: 'info',
            confirmButtonText: 'ตกลง'
        }).then(() => {
            window.location.href = 'logout.php';
        });
    }, redirectTime);
}

document.addEventListener('DOMContentLoaded', function () {
    setupSessionTimeout();
});

// ตัวอย่างการเรียก fetch API
// const formData = new FormData();
// formData.append('username', 'testuser');
// formData.append('password', 'testpass');

// fetch('login_process.php', {
//     method: 'POST',
//     body: formData
// })
//     .then(response => response.text()) // ใช้ text() ชั่วคราวเพื่อดู raw response
//     .then(data => {
//         // console.log('Raw Response:', data); // ตรวจสอบ response ที่ได้
//         try {
//             const jsonData = JSON.parse(data); // แปลงเป็น JSON
//             // console.log('Parsed JSON:', jsonData); // ตรวจสอบ JSON ที่แปลงสำเร็จ
//         } catch (error) {
//             // console.error('JSON Parse Error:', error); // แจ้ง error หากแปลง JSON ไม่สำเร็จ
//         }
//     })
//     .catch(error => {
//         console.error('Fetch Error:', error); // แจ้ง error หาก fetch ล้มเหลว
//     });

function updatePregnancyNumber() {
    const g = document.getElementById('modal-g')?.value || '';
    const p1 = document.getElementById('modal-p1')?.value || '0';
    const p2 = document.getElementById('modal-p2')?.value || '0';
    const p3 = document.getElementById('modal-p3')?.value || '0';
    const p4 = document.getElementById('modal-p4')?.value || '0';
    const last = document.getElementById('modal-last')?.value || '';

    // รูปแบบ: G1P0000 last 1 ปี
    let result = '';
    if (g) {
        result = `G${g}P${p1}${p2}${p3}${p4}`;
        if (last) {
            result += ` last ${last} ปี`;
        }
    }
    document.getElementById('modal-pregnancy_number').value = result;
}

// เพิ่ม event listener ให้ input ทุกตัว
['modal-g', 'modal-p1', 'modal-p2', 'modal-p3', 'modal-p4', 'modal-last'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', updatePregnancyNumber);
});

// ฟังก์ชันคำนวณ pregnancy_number สำหรับ Modal ADD
function updateAddPregnancyNumber() {
    const g = document.querySelector('#exampleModal input[name="g"]')?.value || '';
    const p1 = document.querySelector('#exampleModal input[name="p1"]')?.value || '0';
    const p2 = document.querySelector('#exampleModal input[name="p2"]')?.value || '0';
    const p3 = document.querySelector('#exampleModal input[name="p3"]')?.value || '0';
    const p4 = document.querySelector('#exampleModal input[name="p4"]')?.value || '0';
    const last = document.querySelector('#exampleModal input[name="last"]')?.value || '';
    let result = '';
    if (g) {
        result = `G${g}P${p1}${p2}${p3}${p4}`;
        if (last) {
            result += ` last ${last} ปี`;
        }
    }
    document.querySelector('#exampleModal input[name="pregnancy_number"]').value = result;
}

// ผูก event กับ input ทุกตัวในกลุ่ม G-P-A-L-last ของ Modal ADD
['g','p1','p2','p3','p4','last'].forEach(name => {
    const el = document.querySelector(`#exampleModal input[name="${name}"]`);
    if (el) el.addEventListener('input', updateAddPregnancyNumber);
});

document.addEventListener('DOMContentLoaded', function() {
    // Modal Add
    const hctAdd = document.getElementById('add-hct_last');
    if (hctAdd) {
        setHctBg(hctAdd);
        hctAdd.addEventListener('input', function() { setHctBg(this); });
    }
    // Modal Show
    const hctShow = document.getElementById('modal-hct_last');
    if (hctShow) {
        setHctBg(hctShow);
        hctShow.addEventListener('input', function() { setHctBg(this); });
    }
});
function setHctBg(input) {
    let val = parseFloat(input.value);
    let bg = '';
    if (!isNaN(val)) {
        if (val < 33) {
            bg = 'bg-red-100 text-red-700';
        } else if (val >= 33 && val <= 35) {
            bg = 'bg-yellow-100 text-yellow-700';
        } else if (val > 35) {
            bg = 'bg-green-100 text-green-700';
        }
    }
    input.className = 'w-24 px-3 py-2 border border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-400 ' + bg;
}

// ฟังก์ชันสำหรับแปลงค่า pregnancy_number เป็นค่าแยก G, P1, P2, P3, P4, last
function parsePregnancyNumber(pregnancyNumber) {
    // ตัวอย่าง: "G1P1111 last 1 ปี"
    const result = { g: '', p1: '', p2: '', p3: '', p4: '', last: '' };
    if (!pregnancyNumber) return result;
    // ตรงกับ G1P1234 last 2 ปี หรือ G1P1234
    const match = pregnancyNumber.match(/^G(\d+)P(\d)(\d)(\d)(\d)(?: last (\d+))?/);
    if (match) {
        result.g = match[1];
        result.p1 = match[2];
        result.p2 = match[3];
        result.p3 = match[4];
        result.p4 = match[5];
        result.last = match[6] || '';
    }
    return result;
}

function fillPregnancyFieldsFromNumber(pregnancyNumber) {
    const parsed = parsePregnancyNumber(pregnancyNumber);
    var el;
    el = document.getElementById('modal-g');    if (el) el.value = parsed.g;
    el = document.getElementById('modal-p1');   if (el) el.value = parsed.p1;
    el = document.getElementById('modal-p2');   if (el) el.value = parsed.p2;
    el = document.getElementById('modal-p3');   if (el) el.value = parsed.p3;
    el = document.getElementById('modal-p4');   if (el) el.value = parsed.p4;
    el = document.getElementById('modal-last'); if (el) el.value = parsed.last;
}
