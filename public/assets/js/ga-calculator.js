function calculateGA(lmpDate, edcDate) {
    const today = new Date();
    let gaText = "";
    
    if (edcDate) {
	        // คำนวณจาก EDC confirmed by US
        const edc = new Date(edcDate);
        const diffTime = today - edc;
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
        // 280 วัน (40 สัปดาห์) - จำนวนวันที่เหลือจนถึง EDC
        const pregnancyDays = 280 + diffDays;
        
        if (pregnancyDays >= 0) {
            const weeks = Math.floor(pregnancyDays / 7);
            const days = pregnancyDays % 7;
            gaText = `${weeks} wks. ${days} days by US`;
        } else {
            // กรณียังไม่ถึงวันคลอด
            const weeksLeft = Math.floor(Math.abs(diffDays) / 7);
            const daysLeft = Math.abs(diffDays) % 7;
            const currentWeeks = 40 - weeksLeft - (daysLeft > 0 ? 1 : 0);
            const currentDays = daysLeft > 0 ? 7 - daysLeft : 0;
            gaText = `${currentWeeks} wks. ${currentDays} days by US`;
        }
    } else if (lmpDate) {
        // คำนวณจาก LMP
        const lmp = new Date(lmpDate);
        const diffTime = today - lmp;
        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays >= 0) {
            const weeks = Math.floor(diffDays / 7);
            const days = diffDays % 7;
            gaText = `${weeks} wks. ${days} days by LMP`;
        } else {
            gaText = "Invalid date";
        }
    }
    
    return gaText;
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    const forms = ['', 'modal-']; // รองรับทั้งฟอร์มปกติและ modal
    
    forms.forEach(prefix => {
        const lmpInput = document.getElementById(`${prefix}lmp`);
        const edcInput = document.getElementById(`${prefix}edc_us`);
        const gaInput = document.getElementById(`${prefix}ga`);
        
        function updateGA() {
            if (gaInput) {
                gaInput.value = calculateGA(lmpInput?.value, edcInput?.value);
            }
        }
        
        if (lmpInput) {
            lmpInput.addEventListener('change', updateGA);
        }
        
        if (edcInput) {
            edcInput.addEventListener('change', updateGA);
        }
    });
});

// ฟังก์ชันสำหรับอัพเดท GA เมื่อโหลดข้อมูลในโมดอล
function updateGAInModal(lmpDate, edcDate) {
    const gaInput = document.getElementById('modal-ga');
    if (gaInput) {
        gaInput.value = calculateGA(lmpDate, edcDate);
    }
}

// เพิ่มรองรับ Modal ADD (exampleModal)
document.addEventListener('DOMContentLoaded', function() {
    // สำหรับ Modal ADD
    const lmpInputAdd = document.querySelector('#exampleModal input[name="lmp"]');
    const edcInputAdd = document.querySelector('#exampleModal input[name="edc_us"]');
    const gaInputAdd = document.querySelector('#exampleModal input[name="ga"]');
    function updateGAAdd() {
        if (gaInputAdd) {
            gaInputAdd.value = calculateGA(lmpInputAdd?.value, edcInputAdd?.value);
        }
    }
    if (lmpInputAdd) lmpInputAdd.addEventListener('change', updateGAAdd);
    if (edcInputAdd) edcInputAdd.addEventListener('change', updateGAAdd);

    // เรียกคำนวณ GA ทันทีเมื่อเปิด modal หรือเมื่อโหลดหน้า
    if (gaInputAdd) updateGAAdd();
});