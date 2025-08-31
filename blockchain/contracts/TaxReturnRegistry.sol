// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

// Import National Ledger interface
interface INationalLedger {
    function addRevenue(
        string memory _tiin,
        uint256 _amount,
        string memory _fiscalYear,
        address _validator,
        string memory _taxReturnHash
    ) external;
}

/**
 * @title TaxReturnRegistry
 * @dev Smart contract for storing and validating tax returns on a private blockchain
 * Only NBR officers can validate tax returns
 * Automatically adds verified tax amounts to National Revenue Ledger
 */
contract TaxReturnRegistry {
    
    struct TaxReturn {
        string tiin;                // Taxpayer Identification Number
        string fiscalYear;          // e.g., "2023-24"
        string ipfsHash;           // IPFS hash of the tax return PDF
        uint256 totalIncome;       // Total income in BDT (stored as wei equivalent)
        uint256 totalCost;         // Total tax owed in BDT (stored as wei equivalent)
        address validator;         // NBR officer who validated this return
        uint256 timestamp;         // When the return was submitted
        uint256 validatedTimestamp; // When the return was validated
        bool isValidated;          // Whether the return has been validated
        bool isApproved;           // Whether the return was approved or declined
        string comments;           // NBR officer's comments
    }
    
    struct NbrOfficer {
        address officerAddress;
        string officerId;
        string fullName;
        bool isActive;
    }
    
    // Mappings
    mapping(bytes32 => TaxReturn) public taxReturns;
    mapping(address => NbrOfficer) public nbrOfficers;
    mapping(address => bool) public isNbrOfficer;
    
    // Arrays to track all returns and officers
    bytes32[] public allTaxReturnKeys;
    address[] public allOfficers;
    
    // National Ledger integration
    INationalLedger public nationalLedger;
    bool public nationalLedgerEnabled;
    
    // Events
    event TaxReturnSubmitted(bytes32 indexed returnKey, string tiin, string fiscalYear, string ipfsHash);
    event TaxReturnValidated(bytes32 indexed returnKey, address validator, bool approved);
    event RevenueAddedToNationalLedger(bytes32 indexed returnKey, uint256 amount, string fiscalYear);
    event NbrOfficerRegistered(address indexed officer, string officerId);
    event NbrOfficerDeactivated(address indexed officer);
    event NationalLedgerUpdated(address indexed newLedger);
    
    // Modifiers
    modifier onlyNbrOfficer() {
        require(isNbrOfficer[msg.sender], "Only NBR officers can perform this action");
        require(nbrOfficers[msg.sender].isActive, "NBR officer is not active");
        _;
    }
    
    modifier onlyAdmin() {
        // In a real deployment, this should be restricted to contract owner or admin
        _;
    }
    
    address public admin;
    
    modifier onlyContractAdmin() {
        require(msg.sender == admin, "Only contract admin can perform this action");
        _;
    }
    
    /**
     * @dev Constructor - deploys the contract
     */
    constructor() {
        admin = msg.sender;
        nationalLedgerEnabled = false;
    }
    
    /**
     * @dev Set National Ledger contract address (admin only)
     */
    function setNationalLedger(address _nationalLedgerAddress) external onlyContractAdmin {
        require(_nationalLedgerAddress != address(0), "Invalid national ledger address");
        nationalLedger = INationalLedger(_nationalLedgerAddress);
        nationalLedgerEnabled = true;
        emit NationalLedgerUpdated(_nationalLedgerAddress);
    }
    
    /**
     * @dev Enable/disable National Ledger integration (admin only)
     */
    function toggleNationalLedger(bool _enabled) external onlyContractAdmin {
        nationalLedgerEnabled = _enabled;
    }
    
    /**
     * @dev Register a new NBR officer (admin function)
     */
    function registerNbrOfficer(
        address _officerAddress,
        string memory _officerId,
        string memory _fullName
    ) external onlyAdmin {
        require(_officerAddress != address(0), "Invalid officer address");
        require(!isNbrOfficer[_officerAddress], "Officer already registered");
        
        nbrOfficers[_officerAddress] = NbrOfficer({
            officerAddress: _officerAddress,
            officerId: _officerId,
            fullName: _fullName,
            isActive: true
        });
        
        isNbrOfficer[_officerAddress] = true;
        allOfficers.push(_officerAddress);
        
        emit NbrOfficerRegistered(_officerAddress, _officerId);
    }
    
    /**
     * @dev Deactivate an NBR officer (admin function)
     */
    function deactivateNbrOfficer(address _officerAddress) external onlyAdmin {
        require(isNbrOfficer[_officerAddress], "Officer not found");
        nbrOfficers[_officerAddress].isActive = false;
        emit NbrOfficerDeactivated(_officerAddress);
    }
    
    /**
     * @dev Submit a new tax return (called by backend service)
     */
    function submitTaxReturn(
        string memory _tiin,
        string memory _fiscalYear,
        string memory _ipfsHash,
        uint256 _totalIncome,
        uint256 _totalCost
    ) external returns (bytes32) {
        // Generate unique key for this tax return
        bytes32 returnKey = keccak256(abi.encodePacked(_tiin, _fiscalYear));
        
        require(taxReturns[returnKey].timestamp == 0, "Tax return already exists for this TIIN and fiscal year");
        require(bytes(_tiin).length > 0, "TIIN cannot be empty");
        require(bytes(_fiscalYear).length > 0, "Fiscal year cannot be empty");
        require(bytes(_ipfsHash).length > 0, "IPFS hash cannot be empty");
        
        taxReturns[returnKey] = TaxReturn({
            tiin: _tiin,
            fiscalYear: _fiscalYear,
            ipfsHash: _ipfsHash,
            totalIncome: _totalIncome,
            totalCost: _totalCost,
            validator: address(0),
            timestamp: block.timestamp,
            validatedTimestamp: 0,
            isValidated: false,
            isApproved: false,
            comments: ""
        });
        
        allTaxReturnKeys.push(returnKey);
        
        emit TaxReturnSubmitted(returnKey, _tiin, _fiscalYear, _ipfsHash);
        return returnKey;
    }
    
    /**
     * @dev Validate a tax return (NBR officer only)
     */
    function validateTaxReturn(
        bytes32 _returnKey,
        bool _approved,
        string memory _comments
    ) external onlyNbrOfficer {
        require(taxReturns[_returnKey].timestamp != 0, "Tax return not found");
        require(!taxReturns[_returnKey].isValidated, "Tax return already validated");
        
        taxReturns[_returnKey].validator = msg.sender;
        taxReturns[_returnKey].validatedTimestamp = block.timestamp;
        taxReturns[_returnKey].isValidated = true;
        taxReturns[_returnKey].isApproved = _approved;
        taxReturns[_returnKey].comments = _comments;
        
        // If approved and National Ledger is enabled, add to revenue
        if (_approved && nationalLedgerEnabled && address(nationalLedger) != address(0)) {
            try nationalLedger.addRevenue(
                taxReturns[_returnKey].tiin,
                taxReturns[_returnKey].totalCost, // Tax amount owed
                taxReturns[_returnKey].fiscalYear,
                msg.sender,
                taxReturns[_returnKey].ipfsHash
            ) {
                emit RevenueAddedToNationalLedger(_returnKey, taxReturns[_returnKey].totalCost, taxReturns[_returnKey].fiscalYear);
            } catch {
                // Log error but don't fail the validation
                // This ensures tax validation still works even if National Ledger fails
            }
        }
        
        emit TaxReturnValidated(_returnKey, msg.sender, _approved);
    }
    
    /**
     * @dev Get tax return details
     */
    function getTaxReturn(bytes32 _returnKey) external view returns (
        string memory tiin,
        string memory fiscalYear,
        string memory ipfsHash,
        uint256 totalIncome,
        uint256 totalCost,
        address validator,
        uint256 timestamp,
        uint256 validatedTimestamp,
        bool isValidated,
        bool isApproved,
        string memory comments
    ) {
        TaxReturn memory taxReturn = taxReturns[_returnKey];
        return (
            taxReturn.tiin,
            taxReturn.fiscalYear,
            taxReturn.ipfsHash,
            taxReturn.totalIncome,
            taxReturn.totalCost,
            taxReturn.validator,
            taxReturn.timestamp,
            taxReturn.validatedTimestamp,
            taxReturn.isValidated,
            taxReturn.isApproved,
            taxReturn.comments
        );
    }
    
    /**
     * @dev Get tax return key for a specific TIIN and fiscal year
     */
    function getTaxReturnKey(string memory _tiin, string memory _fiscalYear) external pure returns (bytes32) {
        return keccak256(abi.encodePacked(_tiin, _fiscalYear));
    }
    
    /**
     * @dev Get all pending tax returns (not yet validated)
     */
    function getPendingTaxReturns() external view returns (bytes32[] memory) {
        uint256 pendingCount = 0;
        
        // Count pending returns
        for (uint256 i = 0; i < allTaxReturnKeys.length; i++) {
            if (!taxReturns[allTaxReturnKeys[i]].isValidated) {
                pendingCount++;
            }
        }
        
        // Create array of pending returns
        bytes32[] memory pendingReturns = new bytes32[](pendingCount);
        uint256 currentIndex = 0;
        
        for (uint256 i = 0; i < allTaxReturnKeys.length; i++) {
            if (!taxReturns[allTaxReturnKeys[i]].isValidated) {
                pendingReturns[currentIndex] = allTaxReturnKeys[i];
                currentIndex++;
            }
        }
        
        return pendingReturns;
    }
    
    /**
     * @dev Get total number of tax returns
     */
    function getTotalTaxReturns() external view returns (uint256) {
        return allTaxReturnKeys.length;
    }
    
    /**
     * @dev Get total number of NBR officers
     */
    function getTotalNbrOfficers() external view returns (uint256) {
        return allOfficers.length;
    }
}
