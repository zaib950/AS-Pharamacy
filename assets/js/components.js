/**
 * AS Pharmacy - Components Module
 * Dynamically injects Sidebar and Topbar
 */

const Components = {
    renderSidebar(activePage) {
        const user = window.currentUser || { name: 'Staff User', role: 'staff' };
        
        const initials = user.name.split(' ').map(n => n[0]).join('');
        
        // Inject Favicon & App Title
        if (!document.querySelector('link[rel="icon"]')) {
            const link = document.createElement('link');
            link.rel = 'icon';
            link.href = 'https://cdn-icons-png.flaticon.com/512/822/822143.png';
            document.head.appendChild(link);
        }
        
        const sidebarHtml = `
            <div class="sidebar">
                <div class="sidebar-header">
                    <div class="sidebar-logo-icon">
                        <i class="fa-solid fa-briefcase-medical"></i>
                    </div>
                    <div class="sidebar-logo-text">AS Pharmacy</div>
                </div>
                
                <nav class="sidebar-nav">
                    <div class="nav-label">Main Menu</div>
                    <a href="dashboard.php" class="nav-link ${activePage === 'dashboard' ? 'active' : ''}">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="inventory.php" class="nav-link ${activePage === 'inventory' ? 'active' : ''}">
                        <i class="fa-solid fa-boxes-stacked"></i>
                        <span>Inventory</span>
                    </a>
                    <a href="billing.php" class="nav-link ${activePage === 'billing' ? 'active' : ''}">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        <span>Billing</span>
                    </a>
                    <a href="sales.php" class="nav-link ${activePage === 'sales' ? 'active' : ''}">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>Sales History</span>
                    </a>
                    <a href="attendance.php" class="nav-link ${activePage === 'attendance' ? 'active' : ''}">
                        <i class="fa-solid fa-clipboard-user"></i>
                        <span>Attendance</span>
                    </a>
                    
                    <div class="nav-label" style="margin-top: 1.5rem;">Analytics</div>
                    <a href="reports.php" class="nav-link ${activePage === 'reports' ? 'active' : ''}">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Reports</span>
                    </a>

                    ${user.role === 'admin' ? `
                        <div class="nav-label" style="margin-top: 1.5rem;">Administration</div>
                        <a href="categories.php" class="nav-link ${activePage === 'categories' ? 'active' : ''}">
                            <i class="fa-solid fa-tags"></i>
                            <span>Categories</span>
                        </a>
                        <a href="admin.php" class="nav-link ${activePage === 'admin' ? 'active' : ''}">
                            <i class="fa-solid fa-user-shield"></i>
                            <span>Admin Panel</span>
                        </a>
                    ` : ''}

                    ${user.role === 'staff' ? `
                        <div class="nav-label" style="margin-top: 1.5rem;">Staff Tools</div>
                        <a href="inventory.php" class="nav-link ${activePage === 'inventory' ? 'active' : ''}">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            <span>Manage Stock</span>
                        </a>
                    ` : ''}
                </nav>

                <div class="sidebar-footer">
                    <div class="user-profile" onclick="toggleUserDropdown()">
                        <div class="user-avatar">${initials}</div>
                        <div class="user-info">
                            <span class="user-name">${user.name}</span>
                            <span class="user-role">${user.role.toUpperCase()}</span>
                        </div>
                        <i class="fa-solid fa-chevron-up" style="font-size: 0.7rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
        `;
        
        const container = document.querySelector('.app-container') || document.body;
        if (container) {
            container.insertAdjacentHTML('afterbegin', sidebarHtml);
        }
        
        this.renderScrollNav();
    },

    renderScrollNav() {
        if (document.querySelector('.auth-page')) return;
        if (document.querySelector('.scroll-nav')) return;
        
        const navHtml = `
            <div class="scroll-nav">
                <button class="scroll-btn" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" title="Page Up">
                    <i class="fa-solid fa-chevron-up"></i>
                </button>
                <button class="scroll-btn" onclick="window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'})" title="Page Down">
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', navHtml);
    },

    renderTopbar(pageTitle) {
        const topbarHtml = `
            <header class="topbar">
                <div class="page-info">
                    <h1>${pageTitle}</h1>
                </div>
                
                <div class="topbar-actions">
                    <div class="action-btn" title="Increase Font Size" onclick="UI.changeFontSize(1)">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div class="action-btn" title="Decrease Font Size" onclick="UI.changeFontSize(-1)">
                        <i class="fa-solid fa-minus"></i>
                    </div>
                    <div class="action-btn" title="High Contrast" onclick="UI.toggleContrast()">
                        <i class="fa-solid fa-circle-half-stroke"></i>
                    </div>
                    <div class="action-btn" title="Toggle Theme" onclick="UI.toggleTheme()">
                        <i class="fa-solid fa-moon"></i>
                    </div>
                    <div class="action-btn" title="Notifications" onclick="Components.toggleNotifications(event)">
                        <i class="fa-solid fa-bell"></i>
                        <span id="notifBadge" class="notification-badge"></span>
                    </div>
                    <a href="logout.php" class="action-btn" title="Logout">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                </div>

                <div id="notifDropdown" class="notification-dropdown">
                    <div class="notif-header">
                        <h4 style="font-size: 0.95rem;">Notifications</h4>
                        <span id="notifCount" style="font-size: 0.75rem; color: var(--text-muted);">0 New</span>
                    </div>
                    <div id="notifList" class="notif-list">
                        <!-- Notifications injected here -->
                    </div>
                </div>
            </header>
        `;
        
        const wrapper = document.querySelector('.main-wrapper');
        if (wrapper) {
            wrapper.insertAdjacentHTML('afterbegin', topbarHtml);
            this.updateNotificationState();
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('notifDropdown');
            if (dropdown && !dropdown.contains(e.target) && !e.target.closest('.action-btn')) {
                dropdown.classList.remove('open');
            }
        });
    },

    toggleNotifications(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('notifDropdown');
        dropdown.classList.toggle('open');
        this.updateNotificationState();
    },

    updateNotificationState() {
        const meds = window.allMeds || [];
        const today = new Date();
        const threeMonthsFromNow = new Date();
        threeMonthsFromNow.setMonth(today.getMonth() + 3);

        const alerts = [];

        // Low Stock
        meds.forEach(m => {
            if (m.qty < 10) {
                alerts.push({
                    type: 'low-stock',
                    medId: m.id,
                    title: 'Low Stock Alert',
                    message: `${m.name} is low on stock (${m.qty} left)`,
                    icon: 'fa-boxes-stacked',
                    color: '#ef4444'
                });
            }
            
            // Expiry
            const expiry = new Date(m.expiry);
            if (expiry <= threeMonthsFromNow && expiry >= today) {
                const diffTime = Math.abs(expiry - today);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                alerts.push({
                    type: 'expiry',
                    medId: m.id,
                    title: 'Expiry Warning',
                    message: `${m.name} will expire in ${diffDays} days`,
                    icon: 'fa-hourglass-half',
                    color: '#f59e0b'
                });
            }
        });

        const badge = document.getElementById('notifBadge');
        const count = document.getElementById('notifCount');
        const list = document.getElementById('notifList');

        if (badge) badge.classList.toggle('active', alerts.length > 0);
        if (count) count.innerText = `${alerts.length} Alerts`;

        if (list) {
            if (alerts.length === 0) {
                list.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--text-light); font-size: 0.85rem;">No new notifications</div>';
            } else {
                list.innerHTML = alerts.map(a => `
                    <div class="notif-item" onclick="window.location.href='inventory.php?highlight=${a.medId}'" style="cursor: pointer;">
                        <div class="notif-icon" style="background: ${a.color}15; color: ${a.color};">
                            <i class="fa-solid ${a.icon}"></i>
                        </div>
                        <div class="notif-info">
                            <p style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.15rem;">${a.title}</p>
                            <p style="font-size: 0.75rem; color: var(--text-muted); line-height: 1.3;">${a.message}</p>
                        </div>
                    </div>
                `).join('');
            }
        }
    }
};

const UI = {
    toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        
        const settings = Storage.get(DB.SETTINGS);
        settings.theme = next;
        Storage.set(DB.SETTINGS, settings);
        
        const icon = document.querySelector('.action-btn i.fa-moon, .action-btn i.fa-sun');
        if (icon) {
            icon.className = next === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
    },

    initTheme() {
        const theme = localStorage.getItem('as_theme');
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    },

    toggleContrast() {
        document.body.classList.toggle('high-contrast');
    },

    changeFontSize(delta) {
        const currentSize = parseFloat(getComputedStyle(document.documentElement).fontSize);
        document.documentElement.style.fontSize = (currentSize + delta) + 'px';
    },

    showCookieNotice() {
        // Only show on home page as requested
        const isHomePage = window.location.pathname.endsWith('index.php') || window.location.pathname === '/' || window.location.pathname.endsWith('Final%20Project/');
        if (!isHomePage) return;

        // Only show if not yet consented, OR if consent was given more than 1 hour ago
        const consentTime = localStorage.getItem('cookie_consent_time');
        const oneHour = 60 * 60 * 1000;
        if (consentTime && (Date.now() - parseInt(consentTime)) < oneHour) {
            return; // Within 1 hour — don't show again
        }

        const noticeHtml = `
            <div id="cookie-notice" style="position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); width: calc(100% - 40px); max-width: 600px; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); border: 1px solid var(--primary); padding: 2rem; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); z-index: 10000; display: flex; flex-direction: column; gap: 1.5rem; animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);">
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <div style="width: 50px; height: 50px; background: var(--primary); color: white; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                        <i class="fa-solid fa-cookie-bite"></i>
                    </div>
                    <div>
                        <h4 style="margin-bottom: 0.25rem; color: var(--primary);">We Use Cookies</h4>
                        <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5;">Our pharmacy uses cookies to ensure you get the best experience on our website. By continuing, you agree to our privacy policy.</p>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button class="btn btn-outline" style="padding: 0.75rem 1.5rem;" onclick="UI.acceptCookies(false)">Necessary Only</button>
                    <button class="btn btn-primary" style="padding: 0.75rem 2rem;" onclick="UI.acceptCookies(true)">Accept All</button>
                </div>
            </div>
        `;
        const existing = document.getElementById('cookie-notice');
        if (existing) existing.remove();
        document.body.insertAdjacentHTML('beforeend', noticeHtml);
    },

    acceptCookies(accepted) {
        // Save timestamp so banner stays hidden for 1 hour
        localStorage.setItem('cookie_consent_time', Date.now().toString());
        localStorage.setItem('cookie_consent', accepted ? 'accepted' : 'necessary');
        const notice = document.getElementById('cookie-notice');
        if (notice) notice.remove();
    }
};

const Chatbot = {
    render() {
        const chatHtml = `
            <div id="chatbot-container" class="chatbot-container">
                <div class="chatbot-header">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fa-solid fa-robot"></i>
                        <span>Pharmacy AI Assistant</span>
                    </div>
                    <button onclick="Chatbot.toggle()" style="background: none; border: none; color: white; cursor: pointer;">
                        <i class="fa-solid fa-minus"></i>
                    </button>
                </div>
                <div id="chat-messages" class="chat-messages">
                    <div class="message bot">Hello! How can I help you today with your pharmacy management?</div>
                </div>
                <div class="chat-input-area">
                    <input type="text" id="chatInput" placeholder="Ask a question..." onkeypress="if(event.key === 'Enter') Chatbot.sendMessage()">
                    <button onclick="Chatbot.sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </div>
            <button id="chat-toggle" class="chat-toggle-btn" onclick="Chatbot.toggle()">
                <i class="fa-solid fa-comment-dots"></i>
            </button>
            
            <style>
                .chatbot-container { position: fixed; bottom: 80px; right: 20px; width: 320px; height: 400px; background: var(--surface); border-radius: 12px; box-shadow: var(--shadow-lg); z-index: 1000; display: none; flex-direction: column; overflow: hidden; border: 1px solid var(--border); }
                .chatbot-container.open { display: flex; }
                .chatbot-header { background: var(--primary); color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center; }
                .chat-messages { flex: 1; padding: 1rem; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; background: var(--surface-alt); }
                .message { padding: 0.6rem 0.9rem; border-radius: 12px; font-size: 0.85rem; max-width: 80%; }
                .message.bot { background: white; color: var(--text); align-self: flex-start; border-bottom-left-radius: 2px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
                .message.user { background: var(--primary); color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
                .chat-input-area { padding: 1rem; display: flex; gap: 0.5rem; background: var(--surface); border-top: 1px solid var(--border); }
                .chat-input-area input { flex: 1; padding: 0.5rem 0.8rem; border-radius: 20px; border: 1px solid var(--border); background: var(--surface-alt); font-size: 0.85rem; outline: none; }
                .chat-input-area button { background: var(--primary); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; }
                .chat-toggle-btn { position: fixed; bottom: 20px; right: 20px; width: 50px; height: 50px; border-radius: 50%; background: var(--primary); color: white; border: none; box-shadow: var(--shadow-lg); cursor: pointer; z-index: 1000; font-size: 1.25rem; display: flex; align-items: center; justify-content: center; }
                .high-contrast { filter: contrast(1.5) !important; }
                .high-contrast * { background: black !important; color: white !important; border-color: white !important; }
            </style>
        `;
        document.body.insertAdjacentHTML('beforeend', chatHtml);
    },
    toggle() {
        document.getElementById('chatbot-container').classList.toggle('open');
    },
    async sendMessage() {
        const input = document.getElementById('chatInput');
        const text = input.value.trim();
        if (!text) return;

        const messages = document.getElementById('chat-messages');
        messages.innerHTML += `<div class="message user">${text}</div>`;
        input.value = '';
        messages.scrollTop = messages.scrollHeight;

        // Show typing indicator
        const typingId = 'typing-' + Date.now();
        messages.innerHTML += `<div id="${typingId}" class="message bot">Thinking...</div>`;
        messages.scrollTop = messages.scrollHeight;

        const url = 'chatbot_proxy.php';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ text: text })
            });

            if (!response.ok) {
                throw new Error('Proxy error');
            }

            const data = await response.json();
            const botText = data.candidates[0].content.parts[0].text;
            
            document.getElementById(typingId).innerText = botText;
        } catch (error) {
            console.error('Chatbot Fetch Error:', error);
            document.getElementById(typingId).innerText = "The healthcare assistant is temporarily unavailable. Please try again in a moment.";
        }
        messages.scrollTop = messages.scrollHeight;
    }
};

UI.initTheme();
Components.renderScrollNav();
Chatbot.render();
UI.showCookieNotice();
