document.addEventListener('DOMContentLoaded', function() {
    const avatarInput = document.getElementById('avatar-upload');
    const avatarForm = document.getElementById('avatar-form');
    const avatarPreview = document.querySelector('.profile-avatar');
    const avatarEditBtn = document.querySelector('.avatar-edit');
    
    // Open file dialog when edit button is clicked
    if (avatarEditBtn) {
        avatarEditBtn.addEventListener('click', function() {
            avatarInput.click();
        });
    }
    
    // Preview the selected image
    if (avatarInput) {
        avatarInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                // Check file size
                const maxSize = 2 * 1024 * 1024; // 2MB
                if (this.files[0].size > maxSize) {
                    alert('File is too large. Maximum size is 2MB.');
                    this.value = '';
                    return;
                }
                
                // Check file type
                const fileType = this.files[0].type;
                if (!['image/jpeg', 'image/png', 'image/gif'].includes(fileType)) {
                    alert('Only JPEG, PNG, and GIF files are allowed.');
                    this.value = '';
                    return;
                }
                
                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
                
                // Auto submit the form
                avatarForm.submit();
            }
        });
    }
    
    // Flash messages fade out
    const flashMessages = document.querySelectorAll('.alert');
    if (flashMessages.length > 0) {
        flashMessages.forEach(message => {
            setTimeout(() => {
                message.classList.add('fade-out');
                setTimeout(() => {
                    message.remove();
                }, 500);
            }, 5000);
        });
    }
}); 