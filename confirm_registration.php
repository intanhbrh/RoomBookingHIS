/* confirm_registration.css - Enhanced styling for confirmation page */

/* General styling */
body {
    background-color: #f8f9fa;
  }
  
  .container {
    max-width: 1200px;
  }
  
  /* Card styling */
  .card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
  }
  
  .card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
  }
  
  .card-header {
    border-radius: 12px 12px 0 0 !important;
    border-bottom: none;
    padding: 1.25rem 1.5rem;
    font-weight: 600;
  }
  
  .card-body {
    padding: 1.5rem;
  }
  
  /* Form styling */
  .form-label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
  }
  
  .form-control {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.75rem;
    transition: all 0.3s ease;
  }
  
  .form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    transform: translateY(-1px);
  }
  
  .form-control.is-valid {
    border-color: #28a745;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.8-.77-.8-.77.8-.79.79.8.79-.8.8.79-.8.77.8.77-.8.8-.79-.8-.79.8zm1.4-2.93-.8-.77-.8.77-.8-.77.8-.8.8.8z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
  }
  
  .form-control.is-invalid {
    border-color: #dc3545;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 2.4 2.4m0-2.4L5.8 7'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
  }
  
  .valid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #28a745;
  }
  
  .invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
  }
  
  /* Form check styling */
  .form-check {
    margin-bottom: 0.75rem;
    padding-left: 1.75rem;
  }
  
  .form-check-input {
    margin-top: 0.25rem;
    margin-left: -1.75rem;
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 4px;
    transition: all 0.2s ease;
  }
  
  .form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
    transform: scale(1.05);
  }
  
  .form-check-input:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  }
  
  .form-check-label {
    margin-bottom: 0;
    cursor: pointer;
    line-height: 1.5;
  }
  
  /* Radio button styling */
  input[type="radio"] {
    margin-right: 0.5rem;
  }
  
  input[type="radio"]:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
  }
  
  /* Special needs section */
  #special_needs_details_section {
    margin-top: 1rem;
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #17a2b8;
  }
  
  #relationship_other_section {
    margin-top: 0.5rem;
  }
  
  /* Table styling */
  .table {
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 0;
  }
  
  .table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    padding: 1rem 0.75rem;
  }
  
  .table td {
    padding: 0.75rem;
    vertical-align: middle;
  }
  
  .table-responsive {
    border-radius: 8px;
  }
  
  /* Badge styling */
  .badge {
    font-size: 0.75em;
    padding: 0.35em 0.65em;
    border-radius: 6px;
  }
  
  /* Button styling */
  .btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
    border: none;
  }
  
  .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
  
  .btn:active {
    transform: translateY(0);
  }
  
  .btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
  }
  
  .btn-success:hover {
    background: linear-gradient(135deg, #218838 0%, #1abc9c 100%);
    color: white;
  }
  
  .btn-outline-secondary {
    color: #6c757d;
    border: 2px solid #6c757d;
    background: transparent;
  }
  
  .btn-outline-secondary:hover {
    background-color: #6c757d;
    color: white;
  }
  
  .btn-outline-primary {
    color: #007bff;
    border: 2px solid #007bff;
    background: transparent;
  }
  
  .btn-outline-primary:hover {
    background-color: #007bff;
    color: white;
  }
  
  /* Alert styling */
  .alert {
    border-radius: 8px;
    border: none;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
  }
  
  .alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
  }
  
  .alert-warning {
    background-color: #fff3cd;
    color: #856404;
    border-left: 4px solid #ffc107;
  }
  
  .alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid #17a2b8;
  }
  
  .alert-success {
    background-color: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
  }
  
  /* Loading states */
  .spinner-border-sm {
    width: 1rem;
    height: 1rem;
  }
  
  .btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
  }
  
  /* Error summary styling */
  .error-summary {
    animation: slideIn 0.3s ease;
  }
  
  @keyframes slideIn {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  /* Field error highlighting */
  .field-error {
    background-color: #fff5f5 !important;
    border: 1px solid #fc8181 !important;
    border-radius: 6px !important;
    animation: shake 0.5s ease-in-out;
  }
  
  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
  }
  
  /* Form text styling */
  .form-text {
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #6c757d;
  }
  
  /* Responsive design */
  @media (max-width: 768px) {
    .container {
      padding-left: 15px;
      padding-right: 15px;
    }
    
    .card-body {
      padding: 1rem;
    }
    
    .btn {
      padding: 0.6rem 1.2rem;
      font-size: 0.9rem;
    }
    
    .table th,
    .table td {
      padding: 0.5rem;
      font-size: 0.9rem;
    }
    
    .form-check {
      padding-left: 1.5rem;
    }
    
    .form-check-input {
      margin-left: -1.5rem;
      width: 1rem;
      height: 1rem;
    }
  }
  
  @media (max-width: 576px) {
    .card-header {
      padding: 1rem;
    }
    
    .card-body {
      padding: 0.75rem;
    }
    
    .btn {
      width: 100%;
      margin-bottom: 0.5rem;
    }
    
    .table-responsive {
      font-size: 0.85rem;
    }
    
    h3 {
      font-size: 1.5rem;
    }
    
    h5 {
      font-size: 1.1rem;
    }
  }
  
  /* Focus indicators for accessibility */
  .form-control:focus,
  .form-check-input:focus,
  .btn:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
  }
  
  /* Print styles */
  @media print {
    .btn,
    .alert {
      display: none !important;
    }
    
    .card {
      box-shadow: none !important;
      border: 1px solid #000 !important;
    }
    
    .card-header {
      background: #f8f9fa !important;
      color: #000 !important;
    }
    
    body {
      background: white !important;
    }
  }
  
  /* Auto-save indicator */
  .auto-save-indicator {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 1000;
  }
  
  .auto-save-indicator.show {
    opacity: 1;
  }
  
  /* Enhanced checkbox and radio styling */
  .form-check-input[type="checkbox"] {
    border-radius: 4px;
  }
  
  .form-check-input[type="radio"] {
    border-radius: 50%;
  }
  
  .form-check-input:checked[type="checkbox"] {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='m6 10 3 3 6-6'/%3e%3c/svg%3e");
  }
  
  .form-check-input:checked[type="radio"] {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='2' fill='%23fff'/%3e%3c/svg%3e");
  }
