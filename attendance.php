<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'staff'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - AS Pharmacy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .attendance-hero {
            max-width: 600px;
            margin: 2rem auto;
            text-align: center;
        }

        .kiosk-card {
            background: var(--surface);
            padding: 3rem;
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
            display: flex;
            flex-direction: column;
            gap: 2rem;
            animation: authFadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .clock-large {
            font-size: 4rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -2px;
            font-variant-numeric: tabular-nums;
        }

        .date-large {
            font-size: 1.25rem;
            color: var(--text-muted);
            margin-top: -1.5rem;
            margin-bottom: 1rem;
        }

        .selector-group {
            text-align: left;
        }

        .btn-present {
            height: 70px;
            font-size: 1.25rem;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            color: white;
            border-radius: var(--radius-lg);
            box-shadow: 0 10px 20px var(--accent-glow);
            transition: var(--transition);
        }

        .btn-present:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px var(--accent-glow);
        }

        .btn-present:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            filter: grayscale(1);
        }

        .success-overlay {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: #10b981;
            color: white;
            padding: 1rem 2rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 1rem;
            transform: translateX(150%);
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 1000;
        }

        .success-overlay.show {
            transform: translateX(0);
        }

        .history-section {
            margin-top: 2rem;
            display: block; /* Show by default */
            animation: authFadeIn 0.8s ease forwards;
        }

        .kiosk-card {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md);
            text-align: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar injected here -->
        
        <div class="main-wrapper">
            <!-- Topbar injected here -->
            
            <main class="content-area" style="max-width: 1000px; margin: 0 auto;">
                <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
                    <button class="btn btn-outline" style="padding: 0.6rem 1.2rem; font-size: 0.85rem;" onclick="window.print()">
                        <i class="fa-solid fa-print"></i> Print Report
                    </button>
                </div>

                <!-- 1. Time & Stats Row (Now at Top) -->
                <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                    <!-- Time Card -->
                    <div class="card" style="display: flex; flex-direction: column; justify-content: center; align-items: center; background: linear-gradient(135deg, var(--primary) 0%, #1e293b 100%); color: white; border: none; padding: 2.5rem;">
                        <div id="liveTime" style="font-size: 4rem; font-weight: 800; letter-spacing: -2px; line-height: 1;">00:00:00</div>
                        <div id="liveDate" style="font-size: 1.25rem; opacity: 0.8; font-weight: 500; margin-top: 0.5rem;">Monday, 01 January</div>
                    </div>

                    <div class="card" style="display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 1.5rem;">
                        <div style="text-align: center;">
                            <div style="width: 50px; height: 50px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; margin: 0 auto 1rem;">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <span style="font-weight: 700; color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;">Today Present</span>
                            <div id="todayBadge" style="font-size: 3rem; font-weight: 800; color: #10b981; line-height: 1; margin-top: 0.5rem;">0</div>
                        </div>
                    </div>
                </div>

                <!-- 2. Attendance History (Now below) -->
                <div class="card" style="border-top: 4px solid var(--accent); padding: 0; overflow: hidden;">
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: var(--surface-alt);">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <i class="fa-solid fa-clock-rotate-left" style="color: var(--accent);"></i>
                            <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--primary); letter-spacing: -0.5px;">Attendance History</h3>
                        </div>
                        <span id="totalCount" class="badge badge-success" style="font-weight: 700; padding: 0.5rem 1rem;">0 Records Found</span>
                    </div>
                    <div class="table-container">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: var(--surface);">
                                    <th style="padding: 1.25rem 1.5rem; width: 45%;">Staff Member</th>
                                    <th style="padding: 1.25rem 1.5rem; text-align: center; width: 30%;">Date</th>
                                    <th style="padding: 1.25rem 1.5rem; text-align: right; width: 25%;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="historyBody">
                                <!-- Data injected by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <style>
                @keyframes pulse {
                    0% { transform: scale(1); opacity: 1; }
                    50% { transform: scale(1.5); opacity: 0.5; }
                    100% { transform: scale(1); opacity: 1; }
                }
            </style>
        </div>
    </div>

    <div id="successOverlay" class="success-overlay">
        <i class="fa-solid fa-circle-check fa-lg"></i>
        <div>
            <strong id="successUser">Name</strong>
            <p style="font-size: 0.8rem; opacity: 0.9;">Attendance recorded successfully!</p>
        </div>
    </div>

    <script src="assets/js/storage.js"></script>
    <script src="assets/js/components.js"></script>
    <script>
        Components.renderSidebar('attendance');
        Components.renderTopbar('Attendance System');

        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const dateStr = now.toLocaleDateString([], { weekday: 'long', day: 'numeric', month: 'long' });
            
            const timeEl = document.getElementById('liveTime');
            const dateEl = document.getElementById('liveDate');
            if (timeEl) timeEl.innerText = timeStr;
            if (dateEl) dateEl.innerText = dateStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        const AttendanceManager = {
            init() {
                this.renderHistory();
            },

            renderHistory() {
                const today = new Date().toISOString().split('T')[0];
                const allLogs = Storage.get(DB.ATTENDANCE);
                const users = Storage.get(DB.USERS);
                const activeUserIds = users.map(u => u.id);
                
                // Only show records for users that currently exist in the system
                const validLogs = allLogs.filter(l => activeUserIds.includes(l.userId));
                const sortedLogs = validLogs.sort((a,b) => b.id - a.id);
                const todayLogs = validLogs.filter(l => l.date === today);
                
                // Update Stats
                const heroEl = document.getElementById('heroCount');
                if (heroEl) heroEl.innerText = validLogs.length;

                const todayEl = document.getElementById('todayBadge');
                if (todayEl) todayEl.innerText = todayLogs.length;

                // Daily Rate Calculation
                const rateEl = document.getElementById('rateBadge');
                if (rateEl && users.length > 0) {
                    const rate = Math.round((todayLogs.length / users.length) * 100);
                    rateEl.innerText = `${rate}%`;
                }

                const countEl = document.getElementById('totalCount');
                if (countEl) countEl.innerText = `${validLogs.length} Records Found`;
                
                const body = document.getElementById('historyBody');
                if (!body) return;

                if (sortedLogs.length === 0) {
                    body.innerHTML = '<tr><td colspan="3" style="text-align: center; padding: 3rem; color: var(--text-light);">No attendance records found in system</td></tr>';
                } else {
                    body.innerHTML = sortedLogs.map(l => `
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1.25rem 1.5rem;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="width: 38px; height: 38px; background: var(--accent-glow); color: var(--accent); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; border: 1px solid var(--accent);">
                                        ${l.userName.charAt(0)}
                                    </div>
                                    <span style="font-weight: 700; color: var(--text);">${l.userName}</span>
                                </div>
                            </td>
                            <td style="padding: 1.25rem 1.5rem; text-align: center; font-weight: 600; color: var(--text-muted);">
                                ${new Date(l.date).toLocaleDateString([], { day: 'numeric', month: 'short', year: 'numeric' })}
                            </td>
                            <td style="padding: 1.25rem 1.5rem; text-align: right;">
                                <span class="badge badge-success" style="padding: 0.4rem 1rem; font-size: 0.8rem;">Present</span>
                            </td>
                        </tr>
                    `).join('');
                }
            }
        };

        AttendanceManager.init();
    </script>
</body>
</html>
