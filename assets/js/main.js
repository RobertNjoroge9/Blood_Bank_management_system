// Main JavaScript file for Blood Bank Management System

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    // Phone number validation
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9+]/g, '');
        });
    });
    
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            displayPasswordStrength(strength);
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    return strength;
}

// Display password strength
function displayPasswordStrength(strength) {
    const indicator = document.getElementById('password-strength');
    if (!indicator) return;
    
    const strengths = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    const colors = ['#dc3545', '#ffc107', '#17a2b8', '#28a745', '#28a745'];
    
    indicator.textContent = strengths[strength - 1] || '';
    indicator.style.color = colors[strength - 1] || '#000';
}

// Confirmation dialog for delete actions
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Format date for display
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

// AJAX function for search
function searchDonors() {
    const bloodGroup = document.getElementById('blood_group').value;
    const location = document.getElementById('location').value;
    
    fetch(`/bloodbank/api/search-donors.php?blood_group=${bloodGroup}&location=${location}`)
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data);
        })
        .catch(error => console.error('Error:', error));
}

// Display search results
function displaySearchResults(donors) {
    const resultsDiv = document.getElementById('search-results');
    if (!resultsDiv) return;
    
    if (donors.length === 0) {
        resultsDiv.innerHTML = '<p class="text-center">No donors found.</p>';
        return;
    }
    
    let html = '<div class="row">';
    donors.forEach(donor => {
        html += `
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">${donor.full_name}</h5>
                        <p class="card-text">
                            <strong>Blood Group:</strong> 
                            <span class="badge bg-danger">${donor.blood_group}</span><br>
                            <strong>Phone:</strong> ${donor.phone}<br>
                            <strong>Location:</strong> ${donor.address}
                        </p>
                        <a href="/bloodbank/messages/send.php?receiver_id=${donor.id}" class="btn btn-sm btn-primary">Contact Donor</a>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    resultsDiv.innerHTML = html;
}

// Print function for reports
function printReport() {
    window.print();
}

// Export to CSV
function exportToCSV(data, filename) {
    const csv = convertToCSV(data);
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
}

// Convert data to CSV
function convertToCSV(data) {
    if (!data || data.length === 0) return '';
    
    const headers = Object.keys(data[0]);
    let csv = headers.join(',') + '\n';
    
    data.forEach(row => {
        const values = headers.map(header => {
            const value = row[header] || '';
            return `"${value}"`;
        });
        csv += values.join(',') + '\n';
    });
    
    return csv;
}