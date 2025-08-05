document.addEventListener('DOMContentLoaded', function() {
    // ฟังก์ชันสำหรับแสดง SweetAlert2 confirm dialog
    function showDeleteConfirmDialog(id) {
        return Swal.fire({
            title: 'ยืนยันการลบข้อมูล',
            text: 'คุณต้องการลบข้อมูลนี้ใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ลบข้อมูล',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            reverseButtons: true
        });
    }

    // ฟังก์ชันสำหรับแสดง loading state
    function showLoading() {
        Swal.fire({
            title: 'กำลังดำเนินการ...',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    // ฟังก์ชันสำหรับลบข้อมูล
    async function deleteData(id) {
        try {
            const formData = new FormData();
            formData.append('id', id);

            const response = await fetch('api/delete.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (response.ok) {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: 'ลบข้อมูลเรียบร้อยแล้ว',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    // รีโหลดหน้าเว็บ
                    window.location.reload();
                });
            } else {
                throw new Error(result.message || 'เกิดข้อผิดพลาดในการลบข้อมูล');
            }

        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: error.message,
                confirmButtonText: 'ตกลง'
            });
        }
    }

    // Event delegation สำหรับปุ่มลบ
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.deleteData')) {
            e.preventDefault();
            const button = e.target.closest('.deleteData');
            const id = button.dataset.id;

            const result = await showDeleteConfirmDialog(id);
            if (result.isConfirmed) {
                showLoading();
                await deleteData(id);
            }
        }
    });
});