// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

interface ITaxRegistry {
    function getTotalRevenue() external view returns (uint256, uint256);
}

contract BudgetLedger {
    struct BudgetEntry {
        uint256 projectId;
        uint256 amount;
        uint256 timestamp;
        string description;
        address authorizedBy;
    }

    mapping(uint256 => BudgetEntry) public budgetEntries;
    mapping(address => bool) public authorizedDeductors; // BPPA officials
    
    uint256 public entryCounter;
    uint256 public totalDeducted;
    
    address public owner;
    address public taxRegistryAddress;
    
    event BudgetDeduction(
        uint256 indexed entryId,
        uint256 indexed projectId,
        uint256 amount,
        address indexed authorizedBy,
        uint256 timestamp,
        string description
    );

    modifier onlyOwner() {
        require(msg.sender == owner, "Only owner can perform this action");
        _;
    }

    modifier onlyAuthorizedDeductor() {
        require(authorizedDeductors[msg.sender], "Only authorized deductors can perform this action");
        _;
    }

    constructor(address _taxRegistryAddress) {
        owner = msg.sender;
        taxRegistryAddress = _taxRegistryAddress;
        authorizedDeductors[msg.sender] = true; // Owner is default deductor
    }

    function addAuthorizedDeductor(address _deductor) external onlyOwner {
        authorizedDeductors[_deductor] = true;
    }

    function removeAuthorizedDeductor(address _deductor) external onlyOwner {
        authorizedDeductors[_deductor] = false;
    }

    function setTaxRegistryAddress(address _taxRegistryAddress) external onlyOwner {
        taxRegistryAddress = _taxRegistryAddress;
    }

    function getCurrentBalance() external view returns (uint256) {
        if (taxRegistryAddress == address(0)) {
            return 0;
        }
        
        (, uint256 validatedRevenue) = ITaxRegistry(taxRegistryAddress).getTotalRevenue();
        
        if (validatedRevenue >= totalDeducted) {
            return validatedRevenue - totalDeducted;
        } else {
            return 0;
        }
    }

    function deductBudget(
        uint256 _projectId, 
        uint256 _amount, 
        string memory _description
    ) external onlyAuthorizedDeductor {
        require(_amount > 0, "Amount must be greater than 0");
        require(bytes(_description).length > 0, "Description required");

        uint256 currentBalance = this.getCurrentBalance();
        require(currentBalance >= _amount, "Insufficient budget balance");

        entryCounter++;
        
        budgetEntries[entryCounter] = BudgetEntry({
            projectId: _projectId,
            amount: _amount,
            timestamp: block.timestamp,
            description: _description,
            authorizedBy: msg.sender
        });

        totalDeducted += _amount;

        emit BudgetDeduction(
            entryCounter,
            _projectId,
            _amount,
            msg.sender,
            block.timestamp,
            _description
        );
    }

    function getBudgetEntry(uint256 _entryId) external view returns (BudgetEntry memory) {
        return budgetEntries[_entryId];
    }

    function getProjectDeductions(uint256 _projectId) external view returns (BudgetEntry[] memory) {
        uint256[] memory projectEntryIds = new uint256[](entryCounter);
        uint256 projectEntryCount = 0;

        for (uint256 i = 1; i <= entryCounter; i++) {
            if (budgetEntries[i].projectId == _projectId) {
                projectEntryIds[projectEntryCount] = i;
                projectEntryCount++;
            }
        }

        BudgetEntry[] memory projectEntries = new BudgetEntry[](projectEntryCount);
        for (uint256 i = 0; i < projectEntryCount; i++) {
            projectEntries[i] = budgetEntries[projectEntryIds[i]];
        }

        return projectEntries;
    }

    function getAllDeductions() external view returns (BudgetEntry[] memory) {
        BudgetEntry[] memory allEntries = new BudgetEntry[](entryCounter);
        
        for (uint256 i = 1; i <= entryCounter; i++) {
            allEntries[i - 1] = budgetEntries[i];
        }

        return allEntries;
    }

    function getTotalDeducted() external view returns (uint256) {
        return totalDeducted;
    }

    function getBudgetSummary() external view returns (uint256, uint256, uint256) {
        uint256 currentBalance = this.getCurrentBalance();
        (, uint256 validatedRevenue) = ITaxRegistry(taxRegistryAddress).getTotalRevenue();
        
        return (validatedRevenue, totalDeducted, currentBalance);
    }
}
