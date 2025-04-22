document.addEventListener('DOMContentLoaded', function() {
    // Handle registration form submission
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const formData = new FormData(this);
                // Log form data for debugging
                for (let pair of formData.entries()) {
                    console.log('Register form data:', pair[0], pair[1]);
                }
                
                console.log('Sending registration request...');
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                console.log('Registration response:', data);
                
                if (data.status === 'success') {
                    alert('Registration successful! Please login.');
                    window.location.href = 'login.html';
                } else {
                    alert(data.message || 'Registration failed. Please try again.');
                }
            } catch (error) {
                console.error('Registration error:', error);
                alert('An error occurred. Please try again.');
            }
        });
    }

    // Handle login form submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const formData = new FormData(this);
                // Log form data for debugging
                for (let pair of formData.entries()) {
                    console.log('Login form data:', pair[0], pair[1]);
                }
                
                console.log('Sending login request...');
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin' // Include cookies
                });
                
                console.log('Login response status:', response.status);
                const data = await response.json();
                console.log('Login response data:', data);
                
                if (data.status === 'success') {
                    // Store user data in localStorage for persistence
                    localStorage.setItem('user', JSON.stringify(data.user));
                    console.log('User data stored:', data.user);
                    window.location.href = 'index.html';
                } else {
                    console.error('Login failed:', data.message);
                    alert(data.message || 'Login failed. Please try again.');
                }
            } catch (error) {
                console.error('Login error:', error);
                alert('An error occurred. Please try again.');
            }
        });
    }

    // Add logout functionality
    const logoutBtn = document.createElement('a');
    logoutBtn.href = '#';
    logoutBtn.className = 'auth-btn login-btn';
    logoutBtn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Logout';
    logoutBtn.addEventListener('click', function(e) {
        e.preventDefault();
        localStorage.removeItem('user');
        window.location.href = 'login.html';
    });

    // Update auth buttons based on login state
    function updateAuthButtons() {
        const authButtons = document.querySelector('.auth-buttons');
        if (!authButtons) return;

        const user = localStorage.getItem('user');
        if (user) {
            authButtons.innerHTML = '';
            authButtons.appendChild(logoutBtn);
        }
    }

    // Check authentication status
    function checkAuth() {
        const user = localStorage.getItem('user');
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        
        // Clear any potentially corrupted user data
        if (user && typeof JSON.parse(user) !== 'object') {
            localStorage.removeItem('user');
            return;
        }

        // Handle page access
        if (currentPage === 'index.html') {
            if (!user) {
                window.location.href = 'login.html';
            }
        } else if ((currentPage === 'login.html' || currentPage === 'register.html') && user) {
            window.location.href = 'index.html';
        }
    }

    // Initialize
    checkAuth();
    updateAuthButtons();
}); 