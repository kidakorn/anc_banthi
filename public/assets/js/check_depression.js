// Function to check depression screening answers
function checkDepressionScreening() {
    const q1Value = document.querySelector('input[name="depression_q1"]:checked')?.value;
    const q2Value = document.querySelector('input[name="depression_q2"]:checked')?.value;
    const resultDiv = document.getElementById('screeningResult');

    if (q1Value === '1' || q2Value === '1') {
        resultDiv.classList.remove('hidden');
    } else {
        resultDiv.classList.add('hidden');
    }
}

// Add event listeners to radio buttons
document.querySelectorAll('input[name="depression_q1"], input[name="depression_q2"]').forEach(radio => {
    radio.addEventListener('change', checkDepressionScreening);
});

// เรียกตอนโหลดหน้า (กรณีมีค่าเดิม)
document.addEventListener('DOMContentLoaded', checkDepressionScreening);

// สำหรับ Modal Show
function checkDepressionScreeningShow() {
    // จำกัดเฉพาะใน showModal
    const q1Value = document.querySelector('#showModal input[name="depression_q1"]:checked')?.value;
    const q2Value = document.querySelector('#showModal input[name="depression_q2"]:checked')?.value;
    const resultDiv = document.getElementById('screeningResult');
    if (!resultDiv) return;
    if (q1Value === '1' || q2Value === '1') {
        resultDiv.classList.remove('hidden');
    } else {
        resultDiv.classList.add('hidden');
    }
}
document.querySelectorAll('#showModal input[name="depression_q1"], #showModal input[name="depression_q2"]').forEach(radio => {
    radio.addEventListener('change', checkDepressionScreeningShow);
});
document.addEventListener('DOMContentLoaded', checkDepressionScreeningShow);

// สำหรับ Modal ADD
function checkDepressionScreeningAdd() {
    const q1Value = document.querySelector('#exampleModal input[name="depression_q1"]:checked')?.value;
    const q2Value = document.querySelector('#exampleModal input[name="depression_q2"]:checked')?.value;
    const resultDiv = document.getElementById('addScreeningResult');
    if (!resultDiv) return;
    if (q1Value === '1' || q2Value === '1') {
        resultDiv.classList.remove('hidden');
    } else {
        resultDiv.classList.add('hidden');
    }
}
document.querySelectorAll('#exampleModal input[name="depression_q1"], #exampleModal input[name="depression_q2"]').forEach(radio => {
    radio.addEventListener('change', checkDepressionScreeningAdd);
});
document.addEventListener('DOMContentLoaded', checkDepressionScreeningAdd);
