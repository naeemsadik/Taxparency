// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

/**
 * @title NationalLedger
 * @dev Smart contract for tracking national revenue from verified tax returns
 * and expenses from awarded procurement bids
 */
contract NationalLedger {
    
    struct RevenueEntry {
        string tiin;                // Taxpayer ID
        uint256 amount;             // Tax amount in BDT (wei equivalent)
        uint256 timestamp;          // When added to revenue
        address validator;          // NBR officer who verified
        string taxReturnHash;       // Reference to tax return
        string fiscalYear;          // Tax year
    }
    
    struct ExpenseEntry {
        string procurementId;       // Procurement reference
        uint256 amount;             // Expense amount in BDT (wei equivalent)
        uint256 timestamp;          // When expense was recorded
        address bppaOfficer;        // BPPA officer who approved
        string vendorId;            // Winning vendor ID
        string description;         // Expense description
        bool isFundRequest;         // Whether this is additional fund request
    }
    
    // State variables
    uint256 public totalRevenue;
    uint256 public totalExpense;
    uint256 public availableBalance;
    
    // Arrays to store all entries
    RevenueEntry[] public revenueEntries;
    ExpenseEntry[] public expenseEntries;
    
    // Mappings for quick access
    mapping(string => uint256[]) public revenueByFiscalYear;
    mapping(string => uint256[]) public expenseByProcurement;
    mapping(address => uint256[]) public revenueByValidator;
    mapping(address => uint256[]) public expenseByBppaOfficer;
    
    // Access control
    mapping(address => bool) public isAuthorizedRevenueAdder;
    mapping(address => bool) public isAuthorizedExpenseAdder;
    mapping(address => bool) public isAdmin;
    
    // Events
    event RevenueAdded(string indexed tiin, uint256 amount, string fiscalYear, address validator);
    event ExpenseAdded(string indexed procurementId, uint256 amount, string vendorId, address bppaOfficer);
    event FundRequestExpenseAdded(string indexed procurementId, uint256 amount, string vendorId, address bppaOfficer);
    event AuthorizationGranted(address indexed account, string role);
    event AuthorizationRevoked(address indexed account, string role);
    
    // Modifiers
    modifier onlyAdmin() {
        require(isAdmin[msg.sender], "Only admin can perform this action");
        _;
    }
    
    modifier onlyAuthorizedRevenueAdder() {
        require(isAuthorizedRevenueAdder[msg.sender], "Not authorized to add revenue");
        _;
    }
    
    modifier onlyAuthorizedExpenseAdder() {
        require(isAuthorizedExpenseAdder[msg.sender], "Not authorized to add expense");
        _;
    }
    
    /**
     * @dev Constructor - sets deployer as initial admin
     */
    constructor() {
        isAdmin[msg.sender] = true;
        totalRevenue = 0;
        totalExpense = 0;
        availableBalance = 0;
    }
    
    /**
     * @dev Grant admin privileges
     */
    function grantAdmin(address _account) external onlyAdmin {
        isAdmin[_account] = true;
        emit AuthorizationGranted(_account, "admin");
    }
    
    /**
     * @dev Grant revenue adding privileges (for TaxReturnRegistry contract)
     */
    function grantRevenueAdder(address _account) external onlyAdmin {
        isAuthorizedRevenueAdder[_account] = true;
        emit AuthorizationGranted(_account, "revenue_adder");
    }
    
    /**
     * @dev Grant expense adding privileges (for Procurement/FundRequest contracts)
     */
    function grantExpenseAdder(address _account) external onlyAdmin {
        isAuthorizedExpenseAdder[_account] = true;
        emit AuthorizationGranted(_account, "expense_adder");
    }
    
    /**
     * @dev Revoke admin privileges
     */
    function revokeAdmin(address _account) external onlyAdmin {
        require(_account != msg.sender, "Cannot revoke own admin privileges");
        isAdmin[_account] = false;
        emit AuthorizationRevoked(_account, "admin");
    }
    
    /**
     * @dev Add revenue from verified tax return
     */
    function addRevenue(
        string memory _tiin,
        uint256 _amount,
        string memory _fiscalYear,
        address _validator,
        string memory _taxReturnHash
    ) external onlyAuthorizedRevenueAdder {
        require(_amount > 0, "Revenue amount must be positive");
        require(bytes(_tiin).length > 0, "TIIN cannot be empty");
        require(_validator != address(0), "Invalid validator address");
        
        RevenueEntry memory newEntry = RevenueEntry({
            tiin: _tiin,
            amount: _amount,
            timestamp: block.timestamp,
            validator: _validator,
            taxReturnHash: _taxReturnHash,
            fiscalYear: _fiscalYear
        });
        
        revenueEntries.push(newEntry);
        uint256 entryIndex = revenueEntries.length - 1;
        
        // Update mappings
        revenueByFiscalYear[_fiscalYear].push(entryIndex);
        revenueByValidator[_validator].push(entryIndex);
        
        // Update totals
        totalRevenue += _amount;
        availableBalance += _amount;
        
        emit RevenueAdded(_tiin, _amount, _fiscalYear, _validator);
    }
    
    /**
     * @dev Add expense from awarded procurement
     */
    function addExpense(
        string memory _procurementId,
        uint256 _amount,
        address _bppaOfficer,
        string memory _vendorId,
        string memory _description
    ) external onlyAuthorizedExpenseAdder {
        require(_amount > 0, "Expense amount must be positive");
        require(_amount <= availableBalance, "Insufficient balance for this expense");
        require(bytes(_procurementId).length > 0, "Procurement ID cannot be empty");
        require(_bppaOfficer != address(0), "Invalid BPPA officer address");
        
        ExpenseEntry memory newEntry = ExpenseEntry({
            procurementId: _procurementId,
            amount: _amount,
            timestamp: block.timestamp,
            bppaOfficer: _bppaOfficer,
            vendorId: _vendorId,
            description: _description,
            isFundRequest: false
        });
        
        expenseEntries.push(newEntry);
        uint256 entryIndex = expenseEntries.length - 1;
        
        // Update mappings
        expenseByProcurement[_procurementId].push(entryIndex);
        expenseByBppaOfficer[_bppaOfficer].push(entryIndex);
        
        // Update totals
        totalExpense += _amount;
        availableBalance -= _amount;
        
        emit ExpenseAdded(_procurementId, _amount, _vendorId, _bppaOfficer);
    }
    
    /**
     * @dev Add expense from approved fund request
     */
    function addFundRequestExpense(
        string memory _procurementId,
        uint256 _amount,
        address _bppaOfficer,
        string memory _vendorId,
        string memory _description
    ) external onlyAuthorizedExpenseAdder {
        require(_amount > 0, "Expense amount must be positive");
        require(_amount <= availableBalance, "Insufficient balance for this expense");
        require(bytes(_procurementId).length > 0, "Procurement ID cannot be empty");
        require(_bppaOfficer != address(0), "Invalid BPPA officer address");
        
        ExpenseEntry memory newEntry = ExpenseEntry({
            procurementId: _procurementId,
            amount: _amount,
            timestamp: block.timestamp,
            bppaOfficer: _bppaOfficer,
            vendorId: _vendorId,
            description: _description,
            isFundRequest: true
        });
        
        expenseEntries.push(newEntry);
        uint256 entryIndex = expenseEntries.length - 1;
        
        // Update mappings
        expenseByProcurement[_procurementId].push(entryIndex);
        expenseByBppaOfficer[_bppaOfficer].push(entryIndex);
        
        // Update totals
        totalExpense += _amount;
        availableBalance -= _amount;
        
        emit FundRequestExpenseAdded(_procurementId, _amount, _vendorId, _bppaOfficer);
    }
    
    /**
     * @dev Get current ledger summary
     */
    function getLedgerSummary() external view returns (
        uint256 revenue,
        uint256 expense,
        uint256 balance,
        uint256 totalRevenueEntries,
        uint256 totalExpenseEntries
    ) {
        return (
            totalRevenue,
            totalExpense,
            availableBalance,
            revenueEntries.length,
            expenseEntries.length
        );
    }
    
    /**
     * @dev Get revenue entry by index
     */
    function getRevenueEntry(uint256 _index) external view returns (
        string memory tiin,
        uint256 amount,
        uint256 timestamp,
        address validator,
        string memory taxReturnHash,
        string memory fiscalYear
    ) {
        require(_index < revenueEntries.length, "Revenue entry index out of bounds");
        RevenueEntry memory entry = revenueEntries[_index];
        return (
            entry.tiin,
            entry.amount,
            entry.timestamp,
            entry.validator,
            entry.taxReturnHash,
            entry.fiscalYear
        );
    }
    
    /**
     * @dev Get expense entry by index
     */
    function getExpenseEntry(uint256 _index) external view returns (
        string memory procurementId,
        uint256 amount,
        uint256 timestamp,
        address bppaOfficer,
        string memory vendorId,
        string memory description,
        bool isFundRequest
    ) {
        require(_index < expenseEntries.length, "Expense entry index out of bounds");
        ExpenseEntry memory entry = expenseEntries[_index];
        return (
            entry.procurementId,
            entry.amount,
            entry.timestamp,
            entry.bppaOfficer,
            entry.vendorId,
            entry.description,
            entry.isFundRequest
        );
    }
    
    /**
     * @dev Get revenue entries for a specific fiscal year
     */
    function getRevenueByFiscalYear(string memory _fiscalYear) external view returns (uint256[] memory) {
        return revenueByFiscalYear[_fiscalYear];
    }
    
    /**
     * @dev Get expense entries for a specific procurement
     */
    function getExpenseByProcurement(string memory _procurementId) external view returns (uint256[] memory) {
        return expenseByProcurement[_procurementId];
    }
    
    /**
     * @dev Get total revenue for a fiscal year
     */
    function getTotalRevenueByFiscalYear(string memory _fiscalYear) external view returns (uint256) {
        uint256[] memory entries = revenueByFiscalYear[_fiscalYear];
        uint256 total = 0;
        
        for (uint256 i = 0; i < entries.length; i++) {
            total += revenueEntries[entries[i]].amount;
        }
        
        return total;
    }
    
    /**
     * @dev Get total expense for a procurement
     */
    function getTotalExpenseByProcurement(string memory _procurementId) external view returns (uint256) {
        uint256[] memory entries = expenseByProcurement[_procurementId];
        uint256 total = 0;
        
        for (uint256 i = 0; i < entries.length; i++) {
            total += expenseEntries[entries[i]].amount;
        }
        
        return total;
    }
    
    /**
     * @dev Emergency function to adjust balance (admin only)
     */
    function emergencyAdjustBalance(uint256 _newBalance, string memory _reason) external onlyAdmin {
        availableBalance = _newBalance;
        // Note: This is for emergency corrections only and should be logged off-chain
    }
}
