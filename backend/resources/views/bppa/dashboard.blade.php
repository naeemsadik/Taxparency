<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BPPA Officer Dashboard - Taxparency</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card {
            @apply bg-white shadow-lg rounded-lg p-6;
        }
        .btn-primary {
            @apply bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition duration-200;
        }
        .btn-secondary {
            @apply bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition duration-200;
        }
        .btn-success {
            @apply bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition duration-200;
        }
        .btn-danger {
            @apply bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition duration-200;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-blue-800 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold">
                    <i class="fas fa-building mr-2"></i>
                    BPPA Officer Dashboard
                </h1>
            </div>
            <div class="flex items-center space-x-4">
                <span id="officerName">Mr. Rafiqul Islam</span>
                <button onclick="logout()" class="btn-secondary">
                    <i class="fas fa-sign-out-alt mr-1"></i>
                    Logout
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto p-6">
        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Procurements Card -->
            <div class="card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600">Total Procurements</p>
                        <p class="text-2xl font-bold text-blue-600" id="totalProcurements">Loading...</p>
                    </div>
                    <i class="fas fa-file-contract text-3xl text-blue-400"></i>
                </div>
            </div>

            <!-- Pending Fund Requests Card -->
            <div class="card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600">Pending Fund Requests</p>
                        <p class="text-2xl font-bold text-yellow-600" id="pendingFundRequests">Loading...</p>
                    </div>
                    <i class="fas fa-clock text-3xl text-yellow-400"></i>
                </div>
            </div>

            <!-- Approved Vendors Card -->
            <div class="card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600">Approved Vendors</p>
                        <p class="text-2xl font-bold text-green-600" id="approvedVendors">Loading...</p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-400"></i>
                </div>
            </div>

            <!-- Total Fund Amount Card -->
            <div class="card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600">Total Fund Amount</p>
                        <p class="text-lg font-bold text-purple-600" id="totalFundAmount">Loading...</p>
                    </div>
                    <i class="fas fa-money-bill-wave text-3xl text-purple-400"></i>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="bg-white rounded-lg shadow-lg mb-6">
            <div class="flex border-b">
                <button onclick="showTab('dashboard')" id="dashboardTab" class="px-6 py-3 font-medium text-blue-600 border-b-2 border-blue-600 focus:outline-none">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </button>
                <button onclick="showTab('fund-requests')" id="fundRequestsTab" class="px-6 py-3 font-medium text-gray-600 hover:text-blue-600 focus:outline-none">
                    <i class="fas fa-money-check-alt mr-2"></i>Fund Requests
                </button>
                <button onclick="showTab('procurements')" id="procurementsTab" class="px-6 py-3 font-medium text-gray-600 hover:text-blue-600 focus:outline-none">
                    <i class="fas fa-file-contract mr-2"></i>Procurements
                </button>
                <button onclick="showTab('vendors')" id="vendorsTab" class="px-6 py-3 font-medium text-gray-600 hover:text-blue-600 focus:outline-none">
                    <i class="fas fa-building mr-2"></i>Vendors
                </button>
                <button onclick="showTab('national-ledger')" id="nationalLedgerTab" class="px-6 py-3 font-medium text-gray-600 hover:text-blue-600 focus:outline-none">
                    <i class="fas fa-book mr-2"></i>National Ledger
                </button>
            </div>
        </div>

        <!-- Tab Content -->
        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Activities -->
                <div class="card">
                    <h3 class="text-lg font-bold mb-4">
                        <i class="fas fa-history mr-2"></i>Recent Activities
                    </h3>
                    <div id="recentActivities" class="space-y-3">
                        <!-- Activities will be loaded here -->
                    </div>
                </div>

                <!-- Fund Request Statistics Chart -->
                <div class="card">
                    <h3 class="text-lg font-bold mb-4">
                        <i class="fas fa-chart-pie mr-2"></i>Fund Request Statistics
                    </h3>
                    <canvas id="fundRequestChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Fund Requests Tab -->
        <div id="fund-requests" class="tab-content hidden">
            <div class="card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">
                        <i class="fas fa-money-check-alt mr-2"></i>Pending Fund Requests
                    </h3>
                    <button onclick="refreshFundRequests()" class="btn-secondary">
                        <i class="fas fa-sync mr-1"></i>Refresh
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Request ID</th>
                                <th class="px-4 py-2 text-left">Company</th>
                                <th class="px-4 py-2 text-left">Amount</th>
                                <th class="px-4 py-2 text-left">Reason</th>
                                <th class="px-4 py-2 text-left">Days Pending</th>
                                <th class="px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="fundRequestsTable">
                            <!-- Fund requests will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Procurements Tab -->
        <div id="procurements" class="tab-content hidden">
            <div class="card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">
                        <i class="fas fa-file-contract mr-2"></i>My Procurements
                    </h3>
                    <button onclick="createProcurement()" class="btn-primary">
                        <i class="fas fa-plus mr-1"></i>Create New
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Procurement ID</th>
                                <th class="px-4 py-2 text-left">Title</th>
                                <th class="px-4 py-2 text-left">Category</th>
                                <th class="px-4 py-2 text-left">Value</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="procurementsTable">
                            <!-- Procurements will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Vendors Tab -->
        <div id="vendors" class="tab-content hidden">
            <div class="card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">
                        <i class="fas fa-building mr-2"></i>Pending Vendor Approvals
                    </h3>
                    <button onclick="refreshVendors()" class="btn-secondary">
                        <i class="fas fa-sync mr-1"></i>Refresh
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Company Name</th>
                                <th class="px-4 py-2 text-left">Contact Person</th>
                                <th class="px-4 py-2 text-left">License Number</th>
                                <th class="px-4 py-2 text-left">Days Pending</th>
                                <th class="px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="vendorsTable">
                            <!-- Vendors will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- National Ledger Tab -->
        <div id="national-ledger" class="tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Ledger Summary -->
                <div class="card">
                    <h3 class="text-lg font-bold mb-4">
                        <i class="fas fa-book mr-2"></i>National Ledger Summary
                    </h3>
                    <div id="ledgerSummary" class="space-y-3">
                        <!-- Summary will be loaded here -->
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="card">
                    <h3 class="text-lg font-bold mb-4">
                        <i class="fas fa-list mr-2"></i>Recent Transactions
                    </h3>
                    <div id="recentTransactions" class="space-y-3">
                        <!-- Transactions will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fund Request Approval Modal -->
    <div id="fundRequestModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-bold mb-4" id="modalTitle">Approve Fund Request</h3>
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Approved Amount (BDT)</label>
                <input type="number" id="approvedAmount" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Comments</label>
                <textarea id="bppaComments" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
            </div>
            <div class="flex justify-end space-x-2">
                <button onclick="closeFundRequestModal()" class="btn-secondary">Cancel</button>
                <button onclick="submitFundRequestDecision()" class="btn-success" id="modalSubmitBtn">Approve</button>
            </div>
        </div>
    </div>

    <!-- Vendor Approval Modal -->
    <div id="vendorModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-bold mb-4">Approve Vendor</h3>
            <div class="mb-4">
                <p><strong>Company:</strong> <span id="vendorCompany"></span></p>
                <p><strong>Contact:</strong> <span id="vendorContact"></span></p>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Approval Notes</label>
                <textarea id="approvalNotes" class="w-full px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3"></textarea>
            </div>
            <div class="flex justify-end space-x-2">
                <button onclick="closeVendorModal()" class="btn-secondary">Cancel</button>
                <button onclick="submitVendorApproval()" class="btn-success">Approve</button>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let currentRequestId = null;
        let currentVendorId = null;
        let currentAction = null;
        let officerId = 'BPPA001';

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardStats();
            loadRecentActivities();
            loadFundRequestChart();
        });

        // Tab switching
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('[id$="Tab"]').forEach(btn => {
                btn.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                btn.classList.add('text-gray-600');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.remove('hidden');
            
            // Activate tab button
            const activeBtn = document.getElementById(tabName + 'Tab');
            activeBtn.classList.remove('text-gray-600');
            activeBtn.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
            
            // Load tab-specific data
            switch(tabName) {
                case 'fund-requests':
                    loadFundRequests();
                    break;
                case 'procurements':
                    loadProcurements();
                    break;
                case 'vendors':
                    loadVendors();
                    break;
                case 'national-ledger':
                    loadNationalLedger();
                    break;
            }
        }

        // Load dashboard statistics
        async function loadDashboardStats() {
            try {
                const response = await fetch('/api/v1/bppa/dashboard/stats');
                const data = await response.json();
                
                if (data.success) {
                    const stats = data.data;
                    document.getElementById('totalProcurements').textContent = stats.procurements.total;
                    document.getElementById('pendingFundRequests').textContent = stats.fund_requests.pending;
                    document.getElementById('approvedVendors').textContent = stats.vendors.approved;
                    document.getElementById('totalFundAmount').textContent = formatCurrency(stats.fund_requests.total_amount_approved);
                }
            } catch (error) {
                console.error('Error loading dashboard stats:', error);
            }
        }

        // Load fund requests
        async function loadFundRequests() {
            try {
                const response = await fetch('/api/v1/bppa/fund-requests/pending');
                const data = await response.json();
                
                if (data.success) {
                    const requests = data.data.requests;
                    const tableBody = document.getElementById('fundRequestsTable');
                    
                    tableBody.innerHTML = requests.map(request => `
                        <tr class="border-b">
                            <td class="px-4 py-2">${request.request_id}</td>
                            <td class="px-4 py-2">${request.company_name}</td>
                            <td class="px-4 py-2">${formatCurrency(request.requested_amount)}</td>
                            <td class="px-4 py-2">${request.reason}</td>
                            <td class="px-4 py-2">${request.days_pending} days</td>
                            <td class="px-4 py-2">
                                <button onclick="approveFundRequest('${request.request_id}', ${request.requested_amount})" class="btn-success text-sm mr-1">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button onclick="rejectFundRequest('${request.request_id}')" class="btn-danger text-sm">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading fund requests:', error);
            }
        }

        // Load vendors
        async function loadVendors() {
            try {
                const response = await fetch('/api/v1/bppa/vendors/pending');
                const data = await response.json();
                
                if (data.success) {
                    const vendors = data.data.vendors;
                    const tableBody = document.getElementById('vendorsTable');
                    
                    tableBody.innerHTML = vendors.map(vendor => `
                        <tr class="border-b">
                            <td class="px-4 py-2">${vendor.company_name}</td>
                            <td class="px-4 py-2">${vendor.contact_person}</td>
                            <td class="px-4 py-2">${vendor.vendor_license_number}</td>
                            <td class="px-4 py-2">${vendor.days_pending} days</td>
                            <td class="px-4 py-2">
                                <button onclick="approveVendor(${vendor.id}, '${vendor.company_name}', '${vendor.contact_person}')" class="btn-success text-sm">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading vendors:', error);
            }
        }

        // Fund request approval functions
        function approveFundRequest(requestId, requestedAmount) {
            currentRequestId = requestId;
            currentAction = 'approve';
            document.getElementById('modalTitle').textContent = 'Approve Fund Request';
            document.getElementById('approvedAmount').value = requestedAmount;
            document.getElementById('modalSubmitBtn').textContent = 'Approve';
            document.getElementById('modalSubmitBtn').className = 'btn-success';
            document.getElementById('fundRequestModal').classList.remove('hidden');
        }

        function rejectFundRequest(requestId) {
            currentRequestId = requestId;
            currentAction = 'reject';
            document.getElementById('modalTitle').textContent = 'Reject Fund Request';
            document.getElementById('approvedAmount').style.display = 'none';
            document.getElementById('modalSubmitBtn').textContent = 'Reject';
            document.getElementById('modalSubmitBtn').className = 'btn-danger';
            document.getElementById('fundRequestModal').classList.remove('hidden');
        }

        async function submitFundRequestDecision() {
            const requestId = currentRequestId;
            const action = currentAction;
            
            const payload = {
                bppa_officer_id: officerId,
                bppa_comments: document.getElementById('bppaComments').value
            };

            if (action === 'approve') {
                payload.approved_amount = parseFloat(document.getElementById('approvedAmount').value);
            }

            try {
                const response = await fetch(`/api/v1/bppa/fund-requests/${requestId}/${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                
                if (data.success) {
                    alert(`Fund request ${action}d successfully!`);
                    closeFundRequestModal();
                    loadFundRequests();
                    loadDashboardStats();
                } else {
                    alert(`Error: ${data.message}`);
                }
            } catch (error) {
                console.error('Error submitting fund request decision:', error);
                alert('Error submitting decision. Please try again.');
            }
        }

        // Vendor approval functions
        function approveVendor(vendorId, companyName, contactPerson) {
            currentVendorId = vendorId;
            document.getElementById('vendorCompany').textContent = companyName;
            document.getElementById('vendorContact').textContent = contactPerson;
            document.getElementById('vendorModal').classList.remove('hidden');
        }

        async function submitVendorApproval() {
            const vendorId = currentVendorId;
            const payload = {
                bppa_officer_id: officerId,
                approval_notes: document.getElementById('approvalNotes').value
            };

            try {
                const response = await fetch(`/api/v1/bppa/vendors/approve/${vendorId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                
                if (data.success) {
                    alert('Vendor approved successfully!');
                    closeVendorModal();
                    loadVendors();
                    loadDashboardStats();
                } else {
                    alert(`Error: ${data.message}`);
                }
            } catch (error) {
                console.error('Error approving vendor:', error);
                alert('Error approving vendor. Please try again.');
            }
        }

        // Modal functions
        function closeFundRequestModal() {
            document.getElementById('fundRequestModal').classList.add('hidden');
            document.getElementById('approvedAmount').style.display = 'block';
            document.getElementById('bppaComments').value = '';
            currentRequestId = null;
            currentAction = null;
        }

        function closeVendorModal() {
            document.getElementById('vendorModal').classList.add('hidden');
            document.getElementById('approvalNotes').value = '';
            currentVendorId = null;
        }

        // Utility functions
        function formatCurrency(amount) {
            return '৳' + new Intl.NumberFormat('en-BD').format(amount);
        }

        function refreshFundRequests() {
            loadFundRequests();
        }

        function refreshVendors() {
            loadVendors();
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '/login';
            }
        }

        // Load other data functions (placeholders)
        function loadProcurements() {
            // Implementation for loading procurements
            console.log('Loading procurements...');
        }

        function loadNationalLedger() {
            // Implementation for loading national ledger
            console.log('Loading national ledger...');
        }

        function loadRecentActivities() {
            // Implementation for loading recent activities
            console.log('Loading recent activities...');
        }

        function loadFundRequestChart() {
            // Implementation for fund request chart
            console.log('Loading fund request chart...');
        }

        function createProcurement() {
            // Implementation for creating new procurement
            alert('Create Procurement functionality will be implemented');
        }
    </script>
</body>
</html>
