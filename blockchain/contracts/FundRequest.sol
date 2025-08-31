// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

/**
 * @title FundRequest
 * @dev Smart contract for managing vendor fund requests and BPPA officer approvals
 */
contract FundRequest {
    
    enum RequestStatus {
        Pending,        // Submitted, awaiting review
        Approved,       // Approved by BPPA officer
        Rejected,       // Rejected by BPPA officer
        Funded,         // Funds disbursed
        Cancelled       // Request cancelled
    }
    
    struct FundRequestDetails {
        string requestId;           // Unique request ID
        string procurementId;       // Related procurement ID
        string vendorId;            // Requesting vendor ID
        string companyName;         // Vendor company name
        uint256 requestedAmount;    // Amount requested in BDT
        string reason;              // Reason for additional funds
        string justification;       // Detailed justification
        string supportingDocs;      // IPFS hash for supporting documents
        uint256 submittedTimestamp; // When request was submitted
        uint256 reviewedTimestamp;  // When request was reviewed
        address bppaOfficer;        // BPPA officer who reviewed
        RequestStatus status;       // Current status
        string bppaComments;        // BPPA officer's comments
        uint256 approvedAmount;     // Amount approved (may be less than requested)
        string disbursementRef;     // Reference for fund disbursement
    }
    
    // State variables
    mapping(string => FundRequestDetails) public fundRequests;
    mapping(string => string[]) public procurementFundRequests; // procurement -> request IDs
    mapping(string => string[]) public vendorFundRequests;      // vendor -> request IDs
    mapping(address => string[]) public officerReviewedRequests; // officer -> request IDs
    
    // Access control
    mapping(address => bool) public isVendor;
    mapping(address => bool) public isBppaOfficer;
    mapping(address => bool) public isAdmin;
    
    // Arrays for enumeration
    string[] public allRequestIds;
    
    // Events
    event FundRequestSubmitted(string indexed requestId, string procurementId, string vendorId, uint256 amount);
    event FundRequestApproved(string indexed requestId, address bppaOfficer, uint256 approvedAmount);
    event FundRequestRejected(string indexed requestId, address bppaOfficer, string reason);
    event FundsDisbursed(string indexed requestId, uint256 amount, string disbursementRef);
    event FundRequestCancelled(string indexed requestId, string reason);
    event VendorRegistered(address indexed vendor, string vendorId);
    event BppaOfficerRegistered(address indexed officer);
    
    // Modifiers
    modifier onlyVendor() {
        require(isVendor[msg.sender], "Only registered vendors can perform this action");
        _;
    }
    
    modifier onlyBppaOfficer() {
        require(isBppaOfficer[msg.sender], "Only BPPA officers can perform this action");
        _;
    }
    
    modifier onlyAdmin() {
        require(isAdmin[msg.sender], "Only admin can perform this action");
        _;
    }
    
    modifier validRequest(string memory _requestId) {
        require(bytes(fundRequests[_requestId].requestId).length > 0, "Fund request not found");
        _;
    }
    
    /**
     * @dev Constructor - sets deployer as admin
     */
    constructor() {
        isAdmin[msg.sender] = true;
    }
    
    /**
     * @dev Register vendor (admin function or self-registration)
     */
    function registerVendor(address _vendor, string memory _vendorId) external {
        require(_vendor != address(0), "Invalid vendor address");
        require(bytes(_vendorId).length > 0, "Vendor ID cannot be empty");
        require(msg.sender == _vendor || isAdmin[msg.sender], "Can only register self or admin can register");
        
        isVendor[_vendor] = true;
        emit VendorRegistered(_vendor, _vendorId);
    }
    
    /**
     * @dev Register BPPA officer (admin function)
     */
    function registerBppaOfficer(address _officer) external onlyAdmin {
        require(_officer != address(0), "Invalid officer address");
        isBppaOfficer[_officer] = true;
        emit BppaOfficerRegistered(_officer);
    }
    
    /**
     * @dev Submit a fund request (vendors only)
     */
    function submitFundRequest(
        string memory _requestId,
        string memory _procurementId,
        string memory _vendorId,
        string memory _companyName,
        uint256 _requestedAmount,
        string memory _reason,
        string memory _justification,
        string memory _supportingDocs
    ) external onlyVendor {
        require(bytes(_requestId).length > 0, "Request ID cannot be empty");
        require(bytes(fundRequests[_requestId].requestId).length == 0, "Request ID already exists");
        require(bytes(_procurementId).length > 0, "Procurement ID cannot be empty");
        require(bytes(_vendorId).length > 0, "Vendor ID cannot be empty");
        require(_requestedAmount > 0, "Requested amount must be positive");
        require(bytes(_reason).length > 0, "Reason cannot be empty");
        
        fundRequests[_requestId] = FundRequestDetails({
            requestId: _requestId,
            procurementId: _procurementId,
            vendorId: _vendorId,
            companyName: _companyName,
            requestedAmount: _requestedAmount,
            reason: _reason,
            justification: _justification,
            supportingDocs: _supportingDocs,
            submittedTimestamp: block.timestamp,
            reviewedTimestamp: 0,
            bppaOfficer: address(0),
            status: RequestStatus.Pending,
            bppaComments: "",
            approvedAmount: 0,
            disbursementRef: ""
        });
        
        // Add to tracking arrays
        allRequestIds.push(_requestId);
        procurementFundRequests[_procurementId].push(_requestId);
        vendorFundRequests[_vendorId].push(_requestId);
        
        emit FundRequestSubmitted(_requestId, _procurementId, _vendorId, _requestedAmount);
    }
    
    /**
     * @dev Approve fund request (BPPA officers only)
     */
    function approveFundRequest(
        string memory _requestId,
        uint256 _approvedAmount,
        string memory _bppaComments
    ) external onlyBppaOfficer validRequest(_requestId) {
        require(fundRequests[_requestId].status == RequestStatus.Pending, "Request not pending");
        require(_approvedAmount > 0, "Approved amount must be positive");
        require(_approvedAmount <= fundRequests[_requestId].requestedAmount, "Cannot approve more than requested");
        
        fundRequests[_requestId].status = RequestStatus.Approved;
        fundRequests[_requestId].bppaOfficer = msg.sender;
        fundRequests[_requestId].reviewedTimestamp = block.timestamp;
        fundRequests[_requestId].bppaComments = _bppaComments;
        fundRequests[_requestId].approvedAmount = _approvedAmount;
        
        // Add to officer's reviewed requests
        officerReviewedRequests[msg.sender].push(_requestId);
        
        emit FundRequestApproved(_requestId, msg.sender, _approvedAmount);
    }
    
    /**
     * @dev Reject fund request (BPPA officers only)
     */
    function rejectFundRequest(
        string memory _requestId,
        string memory _bppaComments
    ) external onlyBppaOfficer validRequest(_requestId) {
        require(fundRequests[_requestId].status == RequestStatus.Pending, "Request not pending");
        require(bytes(_bppaComments).length > 0, "Comments required for rejection");
        
        fundRequests[_requestId].status = RequestStatus.Rejected;
        fundRequests[_requestId].bppaOfficer = msg.sender;
        fundRequests[_requestId].reviewedTimestamp = block.timestamp;
        fundRequests[_requestId].bppaComments = _bppaComments;
        
        // Add to officer's reviewed requests
        officerReviewedRequests[msg.sender].push(_requestId);
        
        emit FundRequestRejected(_requestId, msg.sender, _bppaComments);
    }
    
    /**
     * @dev Mark funds as disbursed (BPPA officers or admin only)
     */
    function markFundsDisbursed(
        string memory _requestId,
        string memory _disbursementRef
    ) external validRequest(_requestId) {
        require(isBppaOfficer[msg.sender] || isAdmin[msg.sender], "Only BPPA officers or admin can mark funds disbursed");
        require(fundRequests[_requestId].status == RequestStatus.Approved, "Request not approved");
        require(bytes(_disbursementRef).length > 0, "Disbursement reference required");
        
        fundRequests[_requestId].status = RequestStatus.Funded;
        fundRequests[_requestId].disbursementRef = _disbursementRef;
        
        emit FundsDisbursed(_requestId, fundRequests[_requestId].approvedAmount, _disbursementRef);
    }
    
    /**
     * @dev Cancel fund request (vendor or admin only)
     */
    function cancelFundRequest(
        string memory _requestId,
        string memory _reason
    ) external validRequest(_requestId) {
        require(
            isVendor[msg.sender] || isAdmin[msg.sender], 
            "Only requesting vendor or admin can cancel"
        );
        require(
            fundRequests[_requestId].status == RequestStatus.Pending || 
            fundRequests[_requestId].status == RequestStatus.Approved,
            "Cannot cancel this request"
        );
        require(bytes(_reason).length > 0, "Cancellation reason required");
        
        fundRequests[_requestId].status = RequestStatus.Cancelled;
        
        emit FundRequestCancelled(_requestId, _reason);
    }
    
    /**
     * @dev Get fund request details
     */
    function getFundRequest(string memory _requestId) external view validRequest(_requestId) returns (
        string memory requestId,
        string memory procurementId,
        string memory vendorId,
        string memory companyName,
        uint256 requestedAmount,
        string memory reason,
        uint256 submittedTimestamp,
        RequestStatus status,
        uint256 approvedAmount,
        address bppaOfficer,
        string memory bppaComments
    ) {
        FundRequestDetails memory request = fundRequests[_requestId];
        return (
            request.requestId,
            request.procurementId,
            request.vendorId,
            request.companyName,
            request.requestedAmount,
            request.reason,
            request.submittedTimestamp,
            request.status,
            request.approvedAmount,
            request.bppaOfficer,
            request.bppaComments
        );
    }
    
    /**
     * @dev Get fund requests for a specific procurement
     */
    function getFundRequestsByProcurement(string memory _procurementId) external view returns (string[] memory) {
        return procurementFundRequests[_procurementId];
    }
    
    /**
     * @dev Get fund requests by a specific vendor
     */
    function getFundRequestsByVendor(string memory _vendorId) external view returns (string[] memory) {
        return vendorFundRequests[_vendorId];
    }
    
    /**
     * @dev Get fund requests reviewed by a specific officer
     */
    function getFundRequestsByOfficer(address _officer) external view returns (string[] memory) {
        return officerReviewedRequests[_officer];
    }
    
    /**
     * @dev Get all pending fund requests
     */
    function getPendingFundRequests() external view returns (string[] memory) {
        uint256 pendingCount = 0;
        
        // Count pending requests
        for (uint256 i = 0; i < allRequestIds.length; i++) {
            if (fundRequests[allRequestIds[i]].status == RequestStatus.Pending) {
                pendingCount++;
            }
        }
        
        // Create array of pending request IDs
        string[] memory pendingRequests = new string[](pendingCount);
        uint256 currentIndex = 0;
        
        for (uint256 i = 0; i < allRequestIds.length; i++) {
            if (fundRequests[allRequestIds[i]].status == RequestStatus.Pending) {
                pendingRequests[currentIndex] = allRequestIds[i];
                currentIndex++;
            }
        }
        
        return pendingRequests;
    }
    
    /**
     * @dev Get approved fund requests that are awaiting disbursement
     */
    function getApprovedFundRequests() external view returns (string[] memory) {
        uint256 approvedCount = 0;
        
        // Count approved requests
        for (uint256 i = 0; i < allRequestIds.length; i++) {
            if (fundRequests[allRequestIds[i]].status == RequestStatus.Approved) {
                approvedCount++;
            }
        }
        
        // Create array of approved request IDs
        string[] memory approvedRequests = new string[](approvedCount);
        uint256 currentIndex = 0;
        
        for (uint256 i = 0; i < allRequestIds.length; i++) {
            if (fundRequests[allRequestIds[i]].status == RequestStatus.Approved) {
                approvedRequests[currentIndex] = allRequestIds[i];
                currentIndex++;
            }
        }
        
        return approvedRequests;
    }
    
    /**
     * @dev Get total statistics
     */
    function getStatistics() external view returns (
        uint256 totalRequests,
        uint256 pendingRequests,
        uint256 approvedRequests,
        uint256 rejectedRequests,
        uint256 fundedRequests,
        uint256 totalRequestedAmount,
        uint256 totalApprovedAmount
    ) {
        uint256 pending = 0;
        uint256 approved = 0;
        uint256 rejected = 0;
        uint256 funded = 0;
        uint256 totalRequested = 0;
        uint256 totalApproved = 0;
        
        for (uint256 i = 0; i < allRequestIds.length; i++) {
            FundRequestDetails memory request = fundRequests[allRequestIds[i]];
            
            totalRequested += request.requestedAmount;
            
            if (request.status == RequestStatus.Pending) {
                pending++;
            } else if (request.status == RequestStatus.Approved) {
                approved++;
                totalApproved += request.approvedAmount;
            } else if (request.status == RequestStatus.Rejected) {
                rejected++;
            } else if (request.status == RequestStatus.Funded) {
                funded++;
                totalApproved += request.approvedAmount;
            }
        }
        
        return (
            allRequestIds.length,
            pending,
            approved,
            rejected,
            funded,
            totalRequested,
            totalApproved
        );
    }
    
    /**
     * @dev Get all request IDs
     */
    function getAllRequestIds() external view returns (string[] memory) {
        return allRequestIds;
    }
}
