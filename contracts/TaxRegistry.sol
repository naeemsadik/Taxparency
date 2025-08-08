// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

contract TaxRegistry {
    struct TaxSubmission {
        uint256 id;
        address citizen;
        uint256 amount;
        uint256 timestamp;
        bool validated;
        string documentHash; // IPFS hash for tax documents
        address validatedBy;
    }

    mapping(uint256 => TaxSubmission) public taxSubmissions;
    mapping(address => uint256[]) public citizenSubmissions;
    mapping(address => bool) public authorizedValidators; // NBR officials
    
    uint256 public submissionCounter;
    uint256 public totalTaxRevenue;
    uint256 public totalValidatedRevenue;
    
    address public owner;
    
    event TaxSubmissionEvent(
        uint256 indexed submissionId,
        address indexed citizen,
        uint256 amount,
        uint256 timestamp,
        string documentHash
    );
    
    event TaxValidationEvent(
        uint256 indexed submissionId,
        address indexed validator,
        bool approved,
        uint256 timestamp
    );

    modifier onlyOwner() {
        require(msg.sender == owner, "Only owner can perform this action");
        _;
    }

    modifier onlyValidator() {
        require(authorizedValidators[msg.sender], "Only authorized validators can perform this action");
        _;
    }

    constructor() {
        owner = msg.sender;
        authorizedValidators[msg.sender] = true; // Owner is default validator
    }

    function addValidator(address _validator) external onlyOwner {
        authorizedValidators[_validator] = true;
    }

    function removeValidator(address _validator) external onlyOwner {
        authorizedValidators[_validator] = false;
    }

    function submitTaxReturn(uint256 _amount, string memory _documentHash) external {
        require(_amount > 0, "Tax amount must be greater than 0");
        require(bytes(_documentHash).length > 0, "Document hash required");

        submissionCounter++;
        
        taxSubmissions[submissionCounter] = TaxSubmission({
            id: submissionCounter,
            citizen: msg.sender,
            amount: _amount,
            timestamp: block.timestamp,
            validated: false,
            documentHash: _documentHash,
            validatedBy: address(0)
        });

        citizenSubmissions[msg.sender].push(submissionCounter);
        totalTaxRevenue += _amount; // Add to total (before validation)

        emit TaxSubmissionEvent(submissionCounter, msg.sender, _amount, block.timestamp, _documentHash);
    }

    function validateTaxSubmission(uint256 _submissionId, bool _approved) external onlyValidator {
        require(_submissionId > 0 && _submissionId <= submissionCounter, "Invalid submission ID");
        require(!taxSubmissions[_submissionId].validated, "Submission already validated");

        TaxSubmission storage submission = taxSubmissions[_submissionId];
        submission.validated = true;
        submission.validatedBy = msg.sender;

        if (_approved) {
            totalValidatedRevenue += submission.amount;
        } else {
            // Remove from total if rejected
            totalTaxRevenue -= submission.amount;
        }

        emit TaxValidationEvent(_submissionId, msg.sender, _approved, block.timestamp);
    }

    function getTaxSubmission(uint256 _submissionId) external view returns (TaxSubmission memory) {
        return taxSubmissions[_submissionId];
    }

    function getCitizenSubmissions(address _citizen) external view returns (uint256[] memory) {
        return citizenSubmissions[_citizen];
    }

    function getPendingValidations() external view returns (uint256[] memory) {
        uint256[] memory pending = new uint256[](submissionCounter);
        uint256 pendingCount = 0;

        for (uint256 i = 1; i <= submissionCounter; i++) {
            if (!taxSubmissions[i].validated) {
                pending[pendingCount] = i;
                pendingCount++;
            }
        }

        // Resize array to actual pending count
        uint256[] memory result = new uint256[](pendingCount);
        for (uint256 i = 0; i < pendingCount; i++) {
            result[i] = pending[i];
        }

        return result;
    }

    function getTotalRevenue() external view returns (uint256, uint256) {
        return (totalTaxRevenue, totalValidatedRevenue);
    }
}
