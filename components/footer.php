                    </div>
                </main>
            </div>
        <?php if (isLoggedIn()): ?>
        <?php else: ?>
                </div>
            </main>
        <?php endif; ?>
    </div>
    
    <!-- Toast notifications -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    <!-- Custom JavaScript -->
    <script>
        // Toast notification function
        function showToast(message, type = 'info', duration = 3000) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            let bgColor = 'bg-blue-500';
            let textColor = 'text-white';
            
            switch (type) {
                case 'success':
                    bgColor = 'bg-green-500';
                    break;
                case 'error':
                    bgColor = 'bg-red-500';
                    break;
                case 'warning':
                    bgColor = 'bg-yellow-500';
                    break;
            }
            
            toast.className = `${bgColor} ${textColor} px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full opacity-0`;
            toast.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            container.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.remove();
                    }
                }, 300);
            }, duration);
        }
        
        // Confirm dialog function
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
        
        // Print function
        function printPage() {
            window.print();
        }
        
        // Format currency function
        function formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(amount);
        }
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-auto-hide');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });
        
        // Form validation
        function validateForm(formId) {
            const form = document.getElementById(formId);
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            return isValid;
        }
        
        // AJAX helper function
        function makeRequest(url, method = 'GET', data = null) {
            return fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: data ? JSON.stringify(data) : null
            })
            .then(response => response.json())
            .catch(error => {
                console.error('Request failed:', error);
                showToast('Request failed. Please try again.', 'error');
            });
        }
    </script>
    
    <?php if (isset($additionalJS)): ?>
        <?php echo $additionalJS; ?>
    <?php endif; ?>
</body>
</html>

