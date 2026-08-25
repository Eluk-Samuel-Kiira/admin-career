<?php

namespace App\Http\Requests\Job\JobIndex;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use App\Models\Job\JobPost;

class JobPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');
        $required = $isUpdate ? 'sometimes|required' : 'required';

        // Check if this is a legacy migration (has legacy_id)
        $isLegacy = $this->input('legacy_id') !== null && $this->input('legacy_id') !== '';

        // For legacy migrations, make certain fields optional
        $legacyRequired = $isLegacy ? 'nullable' : $required;

        return [
            // ----------------------------------------------------------------
            // Core Job Information
            // ----------------------------------------------------------------
            'job_title'             => "{$required}|string|max:255",
            'slug'                  => "nullable|string|max:255|unique:job_posts,slug," . ($this->route('id') ?? 'NULL'),
            'job_description'       => "{$required}|string",
            'responsibilities'      => 'nullable|string',
            'skills'                => 'nullable|string',
            'qualifications'        => 'nullable|string',
            'deadline'              => "{$required}|date",
            'application_procedure' => 'nullable|string|max:600',
            'email'                 => 'nullable|string|max:255',
            'telephone'             => 'nullable|string|max:255',

            // ----------------------------------------------------------------
            // Relationships - Legacy migrations can skip these
            // ----------------------------------------------------------------
            'company_id'          => "{$legacyRequired}|integer|exists:companies,id",
            'job_category_id'     => "{$legacyRequired}|integer|exists:job_categories,id",
            'industry_id'         => "{$legacyRequired}|integer|exists:industries,id",
            'job_location_id'     => "{$legacyRequired}|integer|exists:job_locations,id",
            'job_type_id'         => "{$legacyRequired}|integer|exists:job_types,id",
            'experience_level_id' => "{$legacyRequired}|integer|exists:experience_levels,id",
            'education_level_id'  => "{$legacyRequired}|integer|exists:education_levels,id",
            'salary_range_id'     => 'nullable|integer|exists:salary_ranges,id',
            'poster_id'           => "{$legacyRequired}|integer|exists:users,id",

            // ----------------------------------------------------------------
            // Legacy Tracking - Only for migrations
            // ----------------------------------------------------------------
            'legacy_id'            => 'nullable|integer|unique:job_posts,legacy_id,' . ($this->route('id') ?? 'NULL'),
            'legacy_company_id'    => 'nullable|integer',
            'legacy_alias'         => 'nullable|string|max:255',
            'legacy_metadata'      => 'nullable|array',

            // ----------------------------------------------------------------
            // Location Details
            // ----------------------------------------------------------------
            'duty_station'                    => 'nullable|string|max:255',
            'street_address'                  => 'nullable|string',
            'city'                            => 'nullable|string|max:255',
            'state'                           => 'nullable|string|max:255',
            'country'                         => 'nullable|string|max:255',
            'zipcode'                         => 'nullable|string|max:20',
            'country_code'                    => "nullable|string|size:2",
            'applicant_location_requirements' => 'nullable|string',

            // ----------------------------------------------------------------
            // Salary Information
            // ----------------------------------------------------------------
            'salary_amount'  => 'nullable|numeric|min:0|max:99999999.99',
            'currency'       => 'nullable|string|max:10',
            'payment_period' => 'nullable|string|in:hourly,daily,weekly,monthly,yearly',
            'job_source'     => 'required|string|in:competitor_website,whatsapp,newspaper,employer_website,linkedin,other,facebook',
            'base_salary'    => 'nullable|numeric|min:0|max:99999999.99',
            'salary_range_from' => 'nullable|string|max:255',
            'salary_range_to'   => 'nullable|string|max:255',

            // ----------------------------------------------------------------
            // Job Specifications
            // ----------------------------------------------------------------
            'location_type'   => 'nullable|string|in:remote,hybrid,on-site',
            'work_hours'      => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:full-time,part-time,contract,internship,volunteer,temporary,freelance',
            'job_reference'   => 'nullable|string|max:255',
            'duration'        => 'nullable|string|max:255',
            'experience_months' => 'nullable|integer|min:0',

            // ----------------------------------------------------------------
            // SEO
            // ----------------------------------------------------------------
            'meta_title'       => 'nullable|string|max:100',
            'meta_description' => 'nullable|string|max:400',
            'keywords'         => 'nullable|string',
            'canonical_url'    => 'nullable|url|max:255',
            'focus_keyphrase'  => 'nullable|string|max:255',
            'seo_synonyms'     => 'nullable|string',

            // ----------------------------------------------------------------
            // Boolean Flags
            // ----------------------------------------------------------------
            'is_pinged'           => 'nullable|boolean',
            'is_indexed'          => 'nullable|boolean',
            'is_whatsapp_contact' => 'nullable|boolean',
            'is_telephone_call'   => 'nullable|boolean',
            'is_featured'         => 'nullable|boolean',
            'is_urgent'           => 'nullable|boolean',
            'is_active'           => 'nullable|boolean',
            'is_verified'         => 'nullable|boolean',
            'is_simple_job'       => 'nullable|boolean',
            'is_quick_gig'        => 'nullable|boolean',
            'is_application_required' => 'nullable|boolean',

            // ----------------------------------------------------------------
            // Application Requirements
            // ----------------------------------------------------------------
            'is_academic_documents_required' => 'nullable|boolean',
            'is_cover_letter_required'       => 'nullable|boolean',
            'is_resume_required'             => 'nullable|boolean',

            // ----------------------------------------------------------------
            // AI / Performance (optional, usually system-generated)
            // ----------------------------------------------------------------
            'seo_score'              => 'nullable|numeric|min:0|max:100',
            'content_quality_score'  => 'nullable|numeric|min:0|max:100',
            'search_impressions'     => 'nullable|integer|min:0',
            'search_clicks'          => 'nullable|integer|min:0',
            'click_through_rate'     => 'nullable|numeric|min:0|max:100',
            'view_count'             => 'nullable|integer|min:0',
            'application_count'      => 'nullable|integer|min:0',
            'click_count'            => 'nullable|integer|min:0',

            // ----------------------------------------------------------------
            // Timestamps
            // ----------------------------------------------------------------
            'published_at'   => 'nullable|date',
            'featured_until' => 'nullable|date',
            'migrated_at'    => 'nullable|date',
        ];
    }

    /**
     * After validation hook for custom validation
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateLegacyData($validator);
            $this->validateJobDescription($validator);
            $this->validateContactMethods($validator);
            $this->validateApplicationProcedure($validator);
            $this->validateMultipleEmails($validator);
            $this->validateMultipleTelephones($validator);
            $this->validateDeadlineWithFeatured($validator);
            $this->validateRequiredFieldsForNonLegacy($validator);
            $this->validateDeadlineFuture($validator);
        });
    }

    /**
     * Validate that the deadline is in the future (not today or past)
     */
    protected function validateDeadlineFuture($validator)
    {
        // Skip for legacy migrations - they might have past deadlines
        if ($this->input('legacy_id')) {
            return;
        }

        // Skip for updates - jobs can expire
        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            return;
        }

        $deadline = $this->input('deadline');
        
        if (empty($deadline)) {
            return;
        }

        try {
            $deadlineDate = new \DateTime($deadline);
            $today = new \DateTime();
            $today->setTime(0, 0, 0); // Start of today
            
            // Set deadline to end of day for comparison
            $deadlineDate->setTime(23, 59, 59);
            
            if ($deadlineDate <= $today) {
                $validator->errors()->add(
                    'deadline',
                    'The application deadline must be a future date. Today or past dates are not allowed.'
                );
            }
        } catch (\Exception $e) {
            $validator->errors()->add(
                'deadline',
                'The deadline date is invalid. Please provide a valid future date.'
            );
        }
    }

    /**
     * Validate legacy data consistency
     */
    protected function validateLegacyData($validator)
    {
        $legacyId = $this->input('legacy_id');
        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');
        
        // If this is a new record with legacy_id, ensure it's unique
        if (!$isUpdate && !empty($legacyId)) {
            $exists = JobPost::where('legacy_id', $legacyId)->exists();
            if ($exists) {
                $validator->errors()->add(
                    'legacy_id',
                    'A job post with this legacy ID already exists. Please provide a unique legacy ID.'
                );
            }
        }
        
        // If legacy_id is provided, ensure it's a valid integer
        if (!empty($legacyId) && !is_numeric($legacyId)) {
            $validator->errors()->add(
                'legacy_id',
                'The legacy ID must be a valid integer.'
            );
        }
    }

    /**
     * Validate job description doesn't contain phone numbers or emails
     * Skip validation for legacy migrations
     */
    protected function validateJobDescription($validator)
    {
        // Skip this validation for legacy migrations
        if ($this->input('legacy_id')) {
            return;
        }

        $description = $this->input('job_description');
        $email = $this->input('email');
        $telephone = $this->input('telephone');
        
        if (empty($description)) {
            return;
        }
        
        // Pattern to detect email addresses
        $emailPattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';
        
        // Pattern to detect phone numbers (various formats)
        $phonePattern = '/(?:\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}|' .
                        '\+?\d{1,3}[-.\s]?\d{3}[-.\s]?\d{3}[-.\s]?\d{3,4}|' .
                        '\d{10,}/';
        
        $hasEmailInDesc = preg_match($emailPattern, $description);
        $hasPhoneInDesc = preg_match($phonePattern, $description);
        
        // Case 1: No email or phone provided in contact fields
        if (empty($email) && empty($telephone)) {
            if ($hasEmailInDesc || $hasPhoneInDesc) {
                $validator->errors()->add(
                    'job_description',
                    'Job description should not contain email addresses or phone numbers. ' .
                    'Please provide them in the designated contact fields above.'
                );
            }
        }
        
        // Case 2: Email not provided but found in description
        if (empty($email) && $hasEmailInDesc) {
            $validator->errors()->add(
                'email',
                'Email address found in job description. Please provide it in the contact email field.'
            );
        }
        
        // Case 3: Phone not provided but found in description
        if (empty($telephone) && $hasPhoneInDesc) {
            $validator->errors()->add(
                'telephone',
                'Phone number found in job description. Please provide it in the telephone field.'
            );
        }
    }

    /**
     * Validate contact methods based on provided phone number
     */
    protected function validateContactMethods($validator)
    {
        // Skip for legacy migrations
        if ($this->input('legacy_id')) {
            return;
        }

        $telephone = $this->input('telephone');
        $isWhatsappContact = $this->input('is_whatsapp_contact');
        $isTelephoneCall = $this->input('is_telephone_call');
        
        // Check if any telephone number is provided (not just empty)
        $hasTelephone = !empty($telephone);
        
        // If phone number(s) are provided, at least one contact method must be enabled
        if ($hasTelephone) {
            if (!$isWhatsappContact && !$isTelephoneCall) {
                $validator->errors()->add(
                    'is_whatsapp_contact',
                    'When telephone number(s) are provided, you must specify if they\'re for WhatsApp contact and/or phone calls.'
                );
                $validator->errors()->add(
                    'is_telephone_call',
                    'When telephone number(s) are provided, you must specify if they\'re for WhatsApp contact and/or phone calls.'
                );
            }
        } else {
            // If no phone numbers, contact method flags should be false or null
            if ($isWhatsappContact || $isTelephoneCall) {
                $validator->errors()->add(
                    'telephone',
                    'Telephone number(s) are required when enabling WhatsApp contact or phone call options.'
                );
            }
        }
    }

    /**
     * Validate application procedure based on job type
     */
    protected function validateApplicationProcedure($validator)
    {
        // Skip for legacy migrations
        if ($this->input('legacy_id')) {
            return;
        }

        $isSimpleJob = $this->input('is_simple_job');
        $applicationProcedure = $this->input('application_procedure');
        $email = $this->input('email');
        $telephone = $this->input('telephone');
        $isWhatsappContact = $this->input('is_whatsapp_contact');
        $isTelephoneCall = $this->input('is_telephone_call');
        
        // Pattern to detect URLs
        $urlPattern = '/(https?:\/\/[^\s]+|www\.[^\s]+|[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\/[^\s]*)/';
        
        // Check if any contact method is provided
        $hasEmail = !empty($email);
        $hasPhone = !empty($telephone);
        $hasWhatsapp = $isWhatsappContact;
        $hasCall = $isTelephoneCall;
        $hasAnyContact = $hasEmail || $hasPhone || $hasWhatsapp || $hasCall;
        
        // Case 1: Simple Job (is_simple_job = true)
        if ($isSimpleJob) {
            // If NO contact methods are provided (email, phone, WhatsApp, call), then description MUST have a link
            if (!$hasAnyContact) {
                $description = $this->input('job_description');
                $hasLinkInDesc = !empty($description) && preg_match($urlPattern, $description);
                
                if (!$hasLinkInDesc) {
                    $validator->errors()->add(
                        'job_description',
                        'For simple jobs with no contact information (email, phone, WhatsApp, or call), the job description must include a link where applicants can apply.'
                    );
                }
            }
            
            // Application procedure should be empty for simple jobs
            if (!empty($applicationProcedure)) {
                $validator->errors()->add(
                    'application_procedure',
                    'For simple job posts, the application procedure field should be left empty. Application link should be in the job description.'
                );
            }
        } 
        // Case 2: Regular Job (is_simple_job = false or null)
        else {
            // If no contact methods (email, phone, whatsapp, call) are provided
            if (!$hasAnyContact) {
                // Then application_procedure MUST have a link
                $hasLinkInProcedure = !empty($applicationProcedure) && preg_match($urlPattern, $applicationProcedure);
                
                if (!$hasLinkInProcedure) {
                    $validator->errors()->add(
                        'application_procedure',
                        'When no contact email, phone, WhatsApp, or call options are provided, the application procedure must include a link where applicants can apply.'
                    );
                }
            }
            
            // Also ensure job description doesn't have links for regular jobs
            // (Links should be in application_procedure, not job description)
            $description = $this->input('job_description');
            if (!empty($description) && preg_match($urlPattern, $description)) {
                $validator->errors()->add(
                    'job_description',
                    'For regular job posts, please do not include application links in the job description. Use the "Application Procedure" field instead.'
                );
            }
        }
    }

    /**
     * Validate multiple email addresses
     */
    protected function validateMultipleEmails($validator)
    {
        $email = $this->input('email');
        
        if (empty($email)) {
            return;
        }
        
        $emails = array_map('trim', explode(',', $email));
        
        foreach ($emails as $singleEmail) {
            if (!filter_var($singleEmail, FILTER_VALIDATE_EMAIL)) {
                $validator->errors()->add(
                    'email',
                    "The email address '{$singleEmail}' is not valid. Please enter valid email addresses separated by commas."
                );
            }
        }
    }

    /**
     * Validate multiple telephone numbers
     */
    protected function validateMultipleTelephones($validator)
    {
        $telephone = $this->input('telephone');
        
        if (empty($telephone)) {
            return;
        }
        
        $numbers = array_map('trim', explode(',', $telephone));
        
        foreach ($numbers as $number) {
            // Remove spaces and special characters for validation
            $cleaned = preg_replace('/[^0-9+]/', '', $number);
            
            // Validate phone format (adjust pattern as needed)
            if (!preg_match('/^\+?[0-9]{7,15}$/', $cleaned)) {
                $validator->errors()->add(
                    'telephone',
                    "The phone number '{$number}' is not valid. Please enter valid phone numbers separated by commas (e.g., +1234567890, 1234567890)."
                );
            }
        }
    }

    /**
     * Validate deadline with featured_until
     */
    protected function validateDeadlineWithFeatured($validator)
    {
        $deadline = $this->input('deadline');
        $featuredUntil = $this->input('featured_until');
        
        if (!empty($deadline) && !empty($featuredUntil)) {
            try {
                $deadlineDate = new \DateTime($deadline);
                $featuredDate = new \DateTime($featuredUntil);
                
                if ($featuredDate > $deadlineDate) {
                    $validator->errors()->add(
                        'featured_until',
                        'The featured until date must be on or before the application deadline.'
                    );
                }
            } catch (\Exception $e) {
                // Invalid dates will be caught by validation rules
            }
        }
    }

    /**
     * Validate required fields for non-legacy jobs
     */
    protected function validateRequiredFieldsForNonLegacy($validator)
    {
        // Skip for legacy migrations
        if ($this->input('legacy_id')) {
            return;
        }

        $isUpdate = $this->isMethod('PATCH') || $this->isMethod('PUT');
        
        // For new jobs, ensure all required relationships are present
        if (!$isUpdate) {
            $requiredFields = [
                'company_id' => 'company',
                'job_category_id' => 'job category',
                'industry_id' => 'industry',
                'job_location_id' => 'job location',
                'job_type_id' => 'job type',
                'experience_level_id' => 'experience level',
                'education_level_id' => 'education level',
                'poster_id' => 'poster'
            ];
            
            foreach ($requiredFields as $field => $label) {
                if (empty($this->input($field))) {
                    $validator->errors()->add(
                        $field,
                        "The {$label} field is required for new job posts."
                    );
                }
            }
        }
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation()
    {
        // Handle telephone numbers (multiple separated by commas)
        if ($this->has('telephone') && !empty($this->telephone)) {
            $numbers = array_map('trim', explode(',', $this->telephone));
            $numbers = array_filter($numbers);
            $this->merge([
                'telephone' => implode(', ', $numbers)
            ]);
        }
        
        // Handle emails (multiple separated by commas)
        if ($this->has('email') && !empty($this->email)) {
            $emails = array_map('trim', explode(',', $this->email));
            $emails = array_filter($emails);
            $this->merge([
                'email' => implode(', ', $emails)
            ]);
        }
        
        // Ensure boolean flags are properly cast
        $booleanFields = [
            'is_whatsapp_contact', 'is_telephone_call', 'is_featured',
            'is_urgent', 'is_active', 'is_verified', 'is_pinged', 'is_indexed',
            'is_application_required', 'is_academic_documents_required',
            'is_cover_letter_required', 'is_resume_required', 'is_simple_job', 'is_quick_gig'
        ];
        
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                if (is_string($value)) {
                    $this->merge([
                        $field => filter_var($value, FILTER_VALIDATE_BOOLEAN)
                    ]);
                } elseif (is_numeric($value)) {
                    $this->merge([
                        $field => (bool) $value
                    ]);
                }
            }
        }

        // Handle legacy metadata as array
        if ($this->has('legacy_metadata') && is_string($this->legacy_metadata)) {
            $decoded = json_decode($this->legacy_metadata, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge([
                    'legacy_metadata' => $decoded
                ]);
            }
        }
    }

    /**
     * Get custom attributes for validator errors
     */
    public function attributes(): array
    {
        return [
            'company_id' => 'company',
            'job_category_id' => 'job category',
            'industry_id' => 'industry',
            'job_location_id' => 'job location',
            'job_type_id' => 'job type',
            'experience_level_id' => 'experience level',
            'education_level_id' => 'education level',
            'salary_range_id' => 'salary range',
            'poster_id' => 'poster',
            'job_title' => 'job title',
            'job_description' => 'job description',
            'deadline' => 'application deadline',
            'email' => 'contact email(s)',
            'telephone' => 'telephone number(s)',
            'location_type' => 'location type',
            'employment_type' => 'employment type',
            'payment_period' => 'payment period',
            'job_source' => 'job source',
            'meta_title' => 'meta title',
            'meta_description' => 'meta description',
            'canonical_url' => 'canonical URL',
            'work_hours' => 'work hours',
            'duty_station' => 'duty station',
            'salary_amount' => 'salary amount',
            'base_salary' => 'base salary',
            'applicant_location_requirements' => 'applicant location requirements',
            'application_procedure' => 'application procedure',
            'is_simple_job' => 'simple job',
            'is_whatsapp_contact' => 'whatsapp contact',
            'is_telephone_call' => 'phone call',
            'legacy_id' => 'legacy ID',
            'featured_until' => 'featured until date',
            'currency' => 'currency',
            'job_reference' => 'job reference',
            'duration' => 'duration',
            'experience_months' => 'experience months',
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'deadline.date' => 'The :attribute must be a valid date.',
            'featured_until.date' => 'The :attribute must be a valid date.',
            'job_title.max' => 'The :attribute cannot exceed 255 characters.',
            'meta_title.max' => 'The :attribute cannot exceed 100 characters for SEO optimization.',
            'meta_description.max' => 'The :attribute cannot exceed 400 characters for SEO optimization.',
            'salary_amount.numeric' => 'The :attribute must be a valid number.',
            'salary_amount.min' => 'The :attribute must be at least 0.',
            'base_salary.numeric' => 'The :attribute must be a valid number.',
            'base_salary.min' => 'The :attribute must be at least 0.',
            'email.string' => 'The :attribute field must contain valid email addresses separated by commas.',
            'telephone.string' => 'The :attribute field must contain valid phone numbers separated by commas.',
            'legacy_id.unique' => 'A job post with this legacy ID already exists. Please provide a unique legacy ID.',
            'slug.unique' => 'A job post with this slug already exists. Please provide a unique slug.',
            'currency.max' => 'The :attribute cannot exceed 10 characters.',
            'job_reference.max' => 'The :attribute cannot exceed 255 characters.',
            'duration.max' => 'The :attribute cannot exceed 255 characters.',
            'experience_months.integer' => 'The :attribute must be a valid integer.',
            'experience_months.min' => 'The :attribute must be at least 0.',
            'deadline.after' => 'The application deadline must be a future date after today.',
            'deadline.date' => 'The application deadline must be a valid date.',
        ];
    }

    /**
     * Get the validated data with default values for missing fields
     */
    public function validatedWithDefaults(): array
    {
        $data = $this->validated();
        
        // Set default values for boolean fields if not present
        $booleanDefaults = [
            'is_active' => true,
            'is_verified' => false,
            'is_featured' => false,
            'is_urgent' => false,
            'is_simple_job' => false,
            'is_quick_gig' => false,
            'is_pinged' => false,
            'is_indexed' => false,
            'is_whatsapp_contact' => false,
            'is_telephone_call' => false,
            'is_application_required' => false,
            'is_academic_documents_required' => false,
            'is_cover_letter_required' => false,
            'is_resume_required' => true,
        ];
        
        foreach ($booleanDefaults as $field => $default) {
            if (!isset($data[$field])) {
                $data[$field] = $default;
            }
        }
        
        // Set default currency if not provided
        if (!isset($data['currency'])) {
            $data['currency'] = 'AUD';
        }
        
        // Set default location_type if not provided
        if (!isset($data['location_type'])) {
            $data['location_type'] = 'on-site';
        }
        
        // Set default employment_type if not provided
        if (!isset($data['employment_type'])) {
            $data['employment_type'] = 'full-time';
        }
        
        // Set country_code from location if provided and not set
        if (empty($data['country_code']) && !empty($data['job_location_id'])) {
            $location = \App\Models\Job\JobLocation::find($data['job_location_id']);
            if ($location && $location->country) {
                $data['country_code'] = $location->country;
            }
        }
        
        // Set country from location if provided and not set
        if (empty($data['country']) && !empty($data['job_location_id'])) {
            $location = \App\Models\Job\JobLocation::find($data['job_location_id']);
            if ($location && $location->country) {
                $data['country'] = $location->country;
            }
        }
        
        return $data;
    }

    
}