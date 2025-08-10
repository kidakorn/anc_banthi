<div class="flex flex-col sm:flex-row gap-6 w-full md:w-auto">
    <div class="flex flex-col sm:flex-row gap-6 w-full md:w-auto bg-white p-4 rounded-2xl shadow-md">
        <div class="flex-1">
            <label for="date_start" class="block text-center text-sm font-semibold text-gray-800 mb-2">
                วันที่เริ่มต้น
            </label>
            <input type="date" id="date_start" name="date_start"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition"
                value="<?= htmlspecialchars($_GET['date_start'] ?? '') ?>">
        </div>
        <div class="flex-1">
            <label for="date_end" class="block text-center text-sm font-semibold text-gray-800 mb-2">
                วันที่สิ้นสุด
            </label>
            <input type="date" id="date_end" name="date_end"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 transition"
                value="<?= htmlspecialchars($_GET['date_end'] ?? '') ?>">
        </div>
        <div class="flex items-end">
            <button type="button" id="resetDateBtn"
                class="ml-2 px-4 py-2 bg-red-200 hover:bg-gray-300 rounded-lg text-gray-700">
                ล้างวันที่
            </button>
        </div>
    </div>
</div>

<script>
	document.getElementById('resetDateBtn')?.addEventListener('click', () => {
    document.getElementById('date_start').value = '';
    document.getElementById('date_end').value = '';
    // ถ้ามี dropdown limit ให้รีเซ็ตกลับค่าเดิม เช่น 10
    const entriesPerPage = document.getElementById('entriesPerPage');
    if (entriesPerPage && entriesPerPage.value === 'all') {
        entriesPerPage.value = '10';
    }
    loadData(1);
});
</script>