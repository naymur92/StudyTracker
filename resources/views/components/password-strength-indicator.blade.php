@props(['inputId' => 'password'])

<div class="password-strength-indicator mt-2" id="strength-{{ $inputId }}">
    <div class="small text-muted mb-2"><strong>Password must contain:</strong></div>
    <ul class="list-unstyled small mb-0">
        <li class="password-rule" data-rule="length">
            <i class="fas fa-circle text-muted me-1"></i>
            <span>At least 8 characters</span>
        </li>
        <li class="password-rule" data-rule="uppercase">
            <i class="fas fa-circle text-muted me-1"></i>
            <span>At least one uppercase letter (A-Z)</span>
        </li>
        <li class="password-rule" data-rule="lowercase">
            <i class="fas fa-circle text-muted me-1"></i>
            <span>At least one lowercase letter (a-z)</span>
        </li>
        <li class="password-rule" data-rule="number">
            <i class="fas fa-circle text-muted me-1"></i>
            <span>At least one number (0-9)</span>
        </li>
        <li class="password-rule" data-rule="special">
            <i class="fas fa-circle text-muted me-1"></i>
            <span>At least one special character (!@#$%^&*...)</span>
        </li>
        <li class="password-rule" data-rule="nospace">
            <i class="fas fa-circle text-muted me-1"></i>
            <span>No spaces allowed</span>
        </li>
    </ul>
</div>

@push('styles')
    <style>
        .password-rule {
            padding: 4px 0;
            transition: all 0.3s ease;
        }

        .password-rule.valid {
            color: #28a745;
        }

        .password-rule.valid i {
            color: #28a745 !important;
        }

        .password-rule.valid i:before {
            content: "\f058";
            /* fa-check-circle */
        }

        .password-rule.invalid {
            color: #dc3545;
        }

        .password-rule.invalid i {
            color: #dc3545 !important;
        }

        .password-rule.invalid i:before {
            content: "\f057";
            /* fa-times-circle */
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('{{ $inputId }}');
            const strengthIndicator = document.getElementById('strength-{{ $inputId }}');

            if (passwordInput && strengthIndicator) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    const rules = strengthIndicator.querySelectorAll('.password-rule');

                    // Check each rule
                    rules.forEach(rule => {
                        const ruleType = rule.getAttribute('data-rule');
                        let isValid = false;

                        switch (ruleType) {
                            case 'length':
                                isValid = password.length >= 8;
                                break;
                            case 'uppercase':
                                isValid = /[A-Z]/.test(password);
                                break;
                            case 'lowercase':
                                isValid = /[a-z]/.test(password);
                                break;
                            case 'number':
                                isValid = /[0-9]/.test(password);
                                break;
                            case 'special':
                                isValid = /[!@#$%^&*(),.?":{}|<>_\-+=\[\]\\\/`~;]/.test(password);
                                break;
                            case 'nospace':
                                isValid = !/\s/.test(password);
                                break;
                        }

                        // Update rule status
                        if (password.length > 0) {
                            rule.classList.remove('valid', 'invalid');
                            rule.classList.add(isValid ? 'valid' : 'invalid');
                        } else {
                            rule.classList.remove('valid', 'invalid');
                        }
                    });
                });
            }
        });
    </script>
@endpush
