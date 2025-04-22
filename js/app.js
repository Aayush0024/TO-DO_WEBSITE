// Constants
const API_URL = 'http://localhost/classwork/todo/api';
console.log('API URL:', API_URL);

const PRIORITY_COLORS = {
    low: '#22c55e',
    medium: '#f59e0b',
    high: '#ef4444'
};
const REMINDER_CHECK_INTERVAL = 60000; // Check every minute

// DOM Elements
const taskForm = document.getElementById('taskForm');
const taskInput = document.getElementById('taskInput');
const taskList = document.getElementById('taskList');
const filterButtons = document.querySelectorAll('.filter-btn');
const sortSelect = document.getElementById('sortSelect');
const controls = document.querySelector('.controls');
const authButtons = document.querySelector('.auth-buttons');

// State
let tasks = [];
let currentFilter = 'all';
let currentSort = 'date-desc';

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    loadTasks();
    setupEventListeners();
    setupReminderSystem();
    checkAuth();
});

function setupEventListeners() {
    taskForm.addEventListener('submit', handleTaskSubmit);
    filterButtons.forEach(btn => btn.addEventListener('click', handleFilterClick));
    sortSelect.addEventListener('change', handleSortChange);
    
    // Add input animation
    taskInput.addEventListener('focus', () => {
        taskForm.classList.add('form-focused');
    });
    
    taskInput.addEventListener('blur', () => {
        taskForm.classList.remove('form-focused');
    });
}

// API Functions
async function loadTasks() {
    const user = JSON.parse(localStorage.getItem('user'));
    console.log('Current user:', user);
    
    if (!user) return;

    try {
        const url = `${API_URL}/get_tasks.php?user_id=${user.id}`;
        console.log('Fetching tasks from:', url);
        
        const response = await fetch(url);
        if (!response.ok) throw new Error('Failed to fetch tasks');
        
        const text = await response.text(); // Get the raw response text
        console.log('Raw response:', text);
        
        try {
            const result = JSON.parse(text);
            if (result.status === 'success') {
                tasks = result.tasks;
                renderTasks();
            } else {
                throw new Error(result.message || 'Failed to fetch tasks');
            }
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }
    } catch (error) {
        showNotification('Error loading tasks. Please try again.', 'error');
        console.error('Error:', error);
    }
}

async function addTask(taskData) {
    try {
        const response = await fetch(`${API_URL}/add_task.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(taskData)
        });
        
        if (!response.ok) throw new Error('Failed to add task');
        
        const result = await response.json();
        if (result.status === 'success') {
            // Create a new task object with the returned task_id
            const newTask = {
                id: result.task_id,
                task: taskData.task,
                priority: taskData.priority,
                due_date: taskData.due_date,
                completed: false
            };
            tasks.unshift(newTask);
            renderTasks();
            return true;
        }
        throw new Error(result.message || 'Failed to add task');
    } catch (error) {
        showNotification('Error adding task. Please try again.', 'error');
        console.error('Error:', error);
        return false;
    }
}

async function toggleTaskCompletion(taskId) {
    try {
        const response = await fetch(`${API_URL}/update_task.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: taskId })
        });

        if (!response.ok) {
            console.error('Response not OK:', response.status, response.statusText);
            throw new Error('Failed to update task');
        }
        
        const result = await response.json();
        if (result.status === 'success') {
            const taskIndex = tasks.findIndex(t => t.id === taskId);
            if (taskIndex !== -1) {
                tasks[taskIndex].completed = result.task.completed;
                renderTasks();
            }
            return true;
        }
        throw new Error(result.message || 'Failed to update task');
    } catch (error) {
        showNotification('Error updating task. Please try again.', 'error');
        console.error('Error:', error);
        return false;
    }
}

async function deleteTask(taskId) {
    try {
        const response = await fetch(`${API_URL}/delete_task.php?id=${taskId}`, {
            method: 'GET'
        });
        
        if (!response.ok) throw new Error('Failed to delete task');
        
        const result = await response.json();
        if (result.status === 'success') {
            tasks = tasks.filter(task => task.id !== taskId);
            renderTasks();
            showNotification('Task deleted successfully', 'success');
            return true;
        }
        throw new Error(result.message || 'Failed to delete task');
    } catch (error) {
        showNotification('Error deleting task. Please try again.', 'error');
        console.error('Error:', error);
        return false;
    }
}

// UI Functions
function renderTasks() {
    const filteredTasks = filterTasks(tasks);
    const sortedTasks = sortTasks(filteredTasks);
    
    taskList.innerHTML = sortedTasks.map((task, index) => `
        <div class="task-item ${task.completed ? 'completed' : ''}" 
             data-id="${task.id}"
             style="animation: slideIn 0.3s ease-out ${index * 0.1}s forwards">
            <div class="task-content">
                <button class="complete-btn" onclick="handleTaskComplete(${task.id})">
                    ${task.completed ? '<i class="fas fa-check"></i>' : ''}
                </button>
                <span class="task-text">${escapeHtml(task.task)}</span>
                <span class="priority-badge ${task.priority.toLowerCase()}" 
                      style="background-color: ${PRIORITY_COLORS[task.priority.toLowerCase()]}">
                    ${getPriorityIcon(task.priority)} ${task.priority}
                </span>
                ${task.due_date ? `
                    <span class="due-date">
                        <i class="far fa-calendar-alt"></i>
                        ${formatDate(task.due_date)}
                    </span>
                ` : ''}
            </div>
            <button class="delete-btn" onclick="handleTaskDelete(${task.id})" 
                    title="Delete task">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    `).join('');
}

function getPriorityIcon(priority) {
    const icons = {
        high: '<i class="fas fa-arrow-up"></i>',
        medium: '<i class="fas fa-minus"></i>',
        low: '<i class="fas fa-arrow-down"></i>'
    };
    return icons[priority.toLowerCase()] || '';
}

function filterTasks(tasks) {
    switch (currentFilter) {
        case 'active':
            return tasks.filter(task => !task.completed);
        case 'completed':
            return tasks.filter(task => task.completed);
        default:
            return tasks;
    }
}

function sortTasks(tasks) {
    const sortedTasks = [...tasks];
    switch (currentSort) {
        case 'priority':
            return sortedTasks.sort((a, b) => {
                const priorityOrder = { high: 1, medium: 2, low: 3 };
                return priorityOrder[a.priority.toLowerCase()] - priorityOrder[b.priority.toLowerCase()];
            });
        case 'dueDate':
            return sortedTasks.sort((a, b) => {
                if (!a.due_date) return 1;
                if (!b.due_date) return -1;
                return new Date(a.due_date) - new Date(b.due_date);
            });
        default: // created
            return sortedTasks;
    }
}

function updateActiveFilterButton(activeBtn) {
    filterButtons.forEach(btn => btn.classList.remove('active'));
    activeBtn.classList.add('active');
}

// Event Handlers
async function handleTaskSubmit(e) {
    e.preventDefault();
    
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user) {
        showNotification('Please log in to add tasks', 'error');
        return;
    }
    
    const taskText = taskInput.value.trim();
    const priority = document.getElementById('prioritySelect').value;
    const dueDate = document.getElementById('dueDateInput').value;
    
    if (!taskText) {
        showNotification('Please enter a task', 'error');
        return;
    }
    
    const taskData = {
        user_id: user.id,
        task: taskText,
        priority: priority,
        due_date: dueDate || null
    };
    
    try {
        const success = await addTask(taskData);
        if (success) {
            taskInput.value = '';
            document.getElementById('dueDateInput').value = '';
            showNotification('Task added successfully', 'success');
        }
    } catch (error) {
        console.error('Error adding task:', error);
        showNotification('Failed to add task. Please try again.', 'error');
    }
}

async function handleTaskComplete(taskId) {
    const success = await toggleTaskCompletion(taskId);
    if (!success) {
        showNotification('Error updating task. Please try again.', 'error');
    }
}

async function handleTaskDelete(taskId) {
    if (confirm('Are you sure you want to delete this task?')) {
        const success = await deleteTask(taskId);
        if (!success) {
            showNotification('Error deleting task. Please try again.', 'error');
        }
    }
}

function handleFilterClick(e) {
    currentFilter = e.target.dataset.filter;
    updateActiveFilterButton(e.target);
    renderTasks();
}

function handleSortChange(e) {
    currentSort = e.target.value;
    renderTasks();
}

// Utility Functions
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    
    document.body.appendChild(errorDiv);
    
    setTimeout(() => {
        errorDiv.remove();
    }, 3000);
}

function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.classList.add('fade-out');
        setTimeout(() => successDiv.remove(), 300);
    }, 3000);
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

// Reminder System
function setupReminderSystem() {
    checkReminders(); // Check immediately on load
    setInterval(checkReminders, REMINDER_CHECK_INTERVAL); // Check periodically
}

function checkReminders() {
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);
    
    tasks.forEach(task => {
        if (task.completed) return; // Skip completed tasks
        
        const dueDate = new Date(task.due_date);
        
        // Check for overdue tasks
        if (dueDate < now) {
            showReminder(`Task "${task.task}" is overdue!`, 'error');
            return;
        }
        
        // Check for tasks due today
        if (isSameDay(dueDate, now)) {
            showReminder(`Task "${task.task}" is due today!`, 'warning');
            return;
        }
        
        // Check for tasks due tomorrow
        if (isSameDay(dueDate, tomorrow)) {
            showReminder(`Task "${task.task}" is due tomorrow!`, 'info');
        }
    });
}

function showReminder(message, type = 'info') {
    const icons = {
        error: '<i class="fas fa-exclamation-circle"></i>',
        warning: '<i class="fas fa-exclamation-triangle"></i>',
        info: '<i class="fas fa-info-circle"></i>'
    };
    
    const colors = {
        error: '#e74c3c',
        warning: '#f39c12',
        info: '#3498db'
    };
    
    const reminder = document.createElement('div');
    reminder.className = 'reminder-message';
    reminder.innerHTML = `${icons[type]} ${message}`;
    reminder.style.backgroundColor = colors[type];
    
    document.body.appendChild(reminder);
    
    setTimeout(() => {
        reminder.classList.add('fade-out');
        setTimeout(() => reminder.remove(), 300);
    }, 5000);
}

function isSameDay(date1, date2) {
    return date1.getFullYear() === date2.getFullYear() &&
           date1.getMonth() === date2.getMonth() &&
           date1.getDate() === date2.getDate();
}

// Features Modal
function showFeatures() {
    const modal = document.getElementById('featuresModal');
    modal.classList.add('active');
    
    // Add stagger animation to list items
    const items = modal.querySelectorAll('li');
    items.forEach((item, index) => {
        item.style.animation = `slideIn 0.3s ease-out ${index * 0.1}s forwards`;
    });
}

function closeFeatures() {
    const modal = document.getElementById('featuresModal');
    modal.classList.remove('active');
}

// Authentication
function checkAuth() {
    const user = JSON.parse(localStorage.getItem('user'));
    
    if (user) {
        // User is logged in
        if (taskForm) taskForm.style.display = 'grid';
        if (controls) controls.style.display = 'flex';
        if (taskList) taskList.style.display = 'flex';
        
        // Update auth buttons to show user info and logout
        if (authButtons) {
            authButtons.innerHTML = `
                <span class="username">
                    <i class="fas fa-user"></i> ${user.full_name}
                </span>
                <a href="#" class="auth-btn login-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            `;
        }
    } else {
        // User is not logged in
        if (taskForm) taskForm.style.display = 'none';
        if (controls) controls.style.display = 'none';
        if (taskList) {
            taskList.innerHTML = `
                <div class="login-prompt">
                    <i class="fas fa-lock"></i>
                    <h2>Please Login to View Tasks</h2>
                    <p>Create an account or login to start managing your tasks.</p>
                    <div class="auth-actions">
                        <a href="login.html" class="auth-btn login-btn">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="register.html" class="auth-btn register-btn">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </div>
                </div>
            `;
            taskList.style.display = 'block';
        }
    }
}

// Handle logout
window.logout = function() {
    fetch(`${API_URL}/logout.php`)
        .then(response => response.json())
        .then(data => {
            localStorage.removeItem('user');
            window.location.href = 'login.html';
        })
        .catch(error => {
            console.error('Error:', error);
            localStorage.removeItem('user');
            window.location.href = 'login.html';
        });
};

function showNotification(message, type = 'info') {
    const notification = document.getElementById('notification');
    const notificationText = document.getElementById('notificationText');
    
    // Clear any existing timeout
    if (window.notificationTimeout) {
        clearTimeout(window.notificationTimeout);
    }
    
    // Remove existing classes
    notification.classList.remove('success', 'error', 'info');
    
    // Add new class based on type
    notification.classList.add(type);
    
    // Set message and show notification
    notificationText.textContent = message;
    notification.classList.add('show');
    
    // Hide notification after 3 seconds
    window.notificationTimeout = setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
} 