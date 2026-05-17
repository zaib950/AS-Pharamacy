/**
 * AS Pharmacy - Data Storage Module
 * Handles all CRUD operations using localStorage
 */

const DB = {
    USERS: 'as_pharmacy_users',
    MEDS: 'as_pharmacy_medicines',
    SALES: 'as_pharmacy_sales',
    ATTENDANCE: 'as_pharmacy_attendance',
    SETTINGS: 'as_pharmacy_settings',
    SESSION: 'as_pharmacy_session'
};

const Storage = {
    get(key) {
        const data = localStorage.getItem(key);
        try {
            return data ? JSON.parse(data) : [];
        } catch (e) {
            console.error('Error reading from storage', e);
            return [];
        }
    },

    set(key, value) {
        localStorage.setItem(key, JSON.stringify(value));
    },

    init() {
        // Initialize Users
        if (this.get(DB.USERS).length === 0) {
            this.set(DB.USERS, [
                { 
                    id: 1, 
                    name: 'Admin User', 
                    email: 'admin@aspharmacy.com', 
                    password: 'admin', 
                    role: 'admin',
                    dob: '1990-01-01',
                    joined: '2024-01-01'
                },
                { 
                    id: 2, 
                    name: 'Staff Member', 
                    email: 'staff@aspharmacy.com', 
                    password: 'staff', 
                    role: 'staff',
                    dob: '1995-05-15',
                    joined: '2024-02-15'
                }
            ]);
        }

        // Initialize Medicines
        if (this.get(DB.MEDS).length === 0) {
            this.set(DB.MEDS, [
                { id: 1, name: 'Panadol 500mg', category: 'Analgesic', company: 'GSK', price: 35, qty: 500, expiry: '2026-12-31' },
                { id: 2, name: 'Augmentin 625mg', category: 'Antibiotic', company: 'GSK', price: 180, qty: 8, expiry: '2025-08-15' },
                { id: 3, name: 'Gaviscon Liquid', category: 'Antacid', company: 'Reckitt', price: 250, qty: 45, expiry: '2026-06-30' },
                { id: 4, name: 'Vitamin C 1000mg', category: 'Vitamin', company: 'ICI', price: 120, qty: 3, expiry: '2025-05-20' },
                { id: 5, name: 'Metformin 850mg', category: 'Antidiabetic', company: 'Sanofi', price: 95, qty: 200, expiry: '2027-01-15' }
            ]);
        }

        // Initialize Settings
        if (!localStorage.getItem(DB.SETTINGS)) {
            this.set(DB.SETTINGS, { theme: 'light', name: 'AS Pharmacy' });
        }
    },

    // Auth helpers
    getCurrentUser() {
        const session = localStorage.getItem(DB.SESSION);
        return session ? JSON.parse(session) : null;
    },

    login(email, password) {
        const users = this.get(DB.USERS);
        const user = users.find(u => u.email === email && u.password === password);
        if (user) {
            const { password, ...userWithoutPass } = user;
            this.set(DB.SESSION, userWithoutPass);
            return { success: true, user: userWithoutPass };
        }
        return { success: false, message: 'Invalid email or password' };
    },

    logout() {
        localStorage.removeItem(DB.SESSION);
        window.location.href = 'login.php';
    },

    autoMarkAttendance() {
        const user = this.getCurrentUser();
        if (!user) return;

        const today = new Date().toISOString().split('T')[0];
        const attendance = this.get(DB.ATTENDANCE);
        const alreadyMarked = attendance.find(a => a.userId === user.id && a.date === today);

        if (!alreadyMarked) {
            this.add(DB.ATTENDANCE, {
                userId: user.id,
                userName: user.name,
                date: today,
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true }),
                status: 'Present'
            });
        }
    },

    // Generic CRUD
    add(key, item) {
        const data = this.get(key);
        item.id = Date.now();
        data.push(item);
        this.set(key, data);
        return item;
    },

    update(key, id, updates) {
        const data = this.get(key);
        const index = data.findIndex(item => item.id === id);
        if (index !== -1) {
            data[index] = { ...data[index], ...updates };
            this.set(key, data);
            return true;
        }
        return false;
    },

    delete(key, id) {
        const data = this.get(key);
        const filtered = data.filter(item => item.id !== id);
        this.set(key, filtered);
        return true;
    },

    // Reset Password Logic
    verifyResetIdentity(email, dob) {
        const users = this.get(DB.USERS);
        const user = users.find(u => u.email === email);
        if (!user) return { success: false, message: 'Email address not found in our records.' };
        
        // Match DOB
        if (user.dob === dob) {
            return { success: true };
        }
        return { success: false, message: 'Security verification failed. Date of birth does not match.' };
    },

    resetPassword(email, newPassword) {
        const users = this.get(DB.USERS);
        const index = users.findIndex(u => u.email === email);
        if (index !== -1) {
            users[index].password = newPassword;
            this.set(DB.USERS, users);
            return { success: true };
        }
        return { success: false, message: 'User not found. Process failed.' };
    }
};


// Auto-init on load
Storage.init();
Storage.autoMarkAttendance();
