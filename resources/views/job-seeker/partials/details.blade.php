<div class="d-flex align-items-center mb-5">
    <div class="symbol symbol-80px me-4">
        <img src="{{ $formattedSeeker['avatar'] ?? asset('assets/media/avatars/blank.png') }}" alt="{{ $formattedSeeker['full_name'] }}" />
    </div>
    <div>
        <h4 class="fw-bold mb-1">{{ $formattedSeeker['full_name'] }}</h4>
        <div class="text-muted">{{ $formattedSeeker['email'] }}</div>
        @if($formattedSeeker['phone'])
            <div class="text-muted"><i class="bi bi-phone me-1"></i>{{ $formattedSeeker['phone'] }}</div>
        @endif
        <span class="badge badge-light-{{ $formattedSeeker['is_public'] ? 'success' : 'secondary' }}">
            {{ $formattedSeeker['is_public'] ? 'Public' : 'Private' }}
        </span>
    </div>
</div>
<hr>

<div class="row g-5">
    <div class="col-md-6">
        <div class="fw-bold text-muted fs-7">Professional Title</div>
        <div class="fw-semibold">{{ $formattedSeeker['professional_title'] ?? 'N/A' }}</div>
    </div>
    <div class="col-md-6">
        <div class="fw-bold text-muted fs-7">Country</div>
        <div class="fw-semibold">{{ $formattedSeeker['flag'] }} {{ $formattedSeeker['country'] ?? 'N/A' }}</div>
    </div>
    <div class="col-md-6">
        <div class="fw-bold text-muted fs-7">Years of Experience</div>
        <div class="fw-semibold">{{ $formattedSeeker['years_of_experience'] ?? 0 }} years</div>
    </div>
    <div class="col-md-6">
        <div class="fw-bold text-muted fs-7">City</div>
        <div class="fw-semibold">{{ $formattedSeeker['city'] ?? 'N/A' }}</div>
    </div>
    
    <div class="col-md-6">
        <div class="fw-bold text-muted fs-7">Applied Jobs</div>
        <div class="fw-semibold">{{ $formattedSeeker['applied_count'] ?? 0 }}</div>
    </div>
    <div class="col-md-6">
        <div class="fw-bold text-muted fs-7">Saved Jobs</div>
        <div class="fw-semibold">{{ $formattedSeeker['saved_count'] ?? 0 }}</div>
    </div>
    
    <div class="col-12">
        <div class="fw-bold text-muted fs-7">Skills</div>
        <div class="d-flex flex-wrap gap-2 mt-2">
            @php
                $skills = $formattedSeeker['skills'] ?? '';
                if (is_string($skills)) {
                    $skills = explode(',', $skills);
                } elseif (!is_array($skills)) {
                    $skills = [];
                }
            @endphp
            @forelse($skills as $skill)
                @if(trim($skill))
                    <span class="badge badge-light-primary">{{ trim($skill) }}</span>
                @endif
            @empty
                <span class="text-muted">No skills listed</span>
            @endforelse
        </div>
    </div>
    
    <div class="col-12">
        <div class="fw-bold text-muted fs-7">Professional Summary</div>
        <div class="fw-semibold">{{ $formattedSeeker['professional_summary'] ?? 'N/A' }}</div>
    </div>
    
    <div class="col-12">
        <div class="fw-bold text-muted fs-7">Languages</div>
        <div class="d-flex flex-wrap gap-2 mt-2">
            @php
                $languages = $formattedSeeker['languages'] ?? [];
                if (is_string($languages)) {
                    $languages = json_decode($languages, true) ?? [];
                } elseif (!is_array($languages)) {
                    $languages = [];
                }
            @endphp
            @forelse($languages as $lang)
                @if(trim($lang))
                    <span class="badge badge-light-secondary">{{ trim($lang) }}</span>
                @endif
            @empty
                <span class="text-muted">No languages listed</span>
            @endforelse
        </div>
    </div>
    
    <div class="col-12">
        <div class="fw-bold text-muted fs-7">Links</div>
        <div class="d-flex gap-3 mt-2 flex-wrap">
            @if(!empty($formattedSeeker['linkedin_url']))
                <a href="{{ $formattedSeeker['linkedin_url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-linkedin me-1"></i>LinkedIn
                </a>
            @endif
            @if(!empty($formattedSeeker['github_url']))
                <a href="{{ $formattedSeeker['github_url'] }}" target="_blank" class="btn btn-sm btn-outline-dark">
                    <i class="bi bi-github me-1"></i>GitHub
                </a>
            @endif
            @if(!empty($formattedSeeker['portfolio_url']))
                <a href="{{ $formattedSeeker['portfolio_url'] }}" target="_blank" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-globe me-1"></i>Portfolio
                </a>
            @endif
        </div>
    </div>
    
    {{-- ✅ CV FILES SECTION - Properly shows all CVs with correct URLs --}}
    <div class="col-12">
        <div class="fw-bold text-muted fs-7">CV Files</div>
        <div class="d-flex flex-column gap-2 mt-2">
            @php
                $cvFiles = $formattedSeeker['cv_files'] ?? [];
                if (is_string($cvFiles)) {
                    $cvFiles = json_decode($cvFiles, true) ?? [];
                }
                if (!is_array($cvFiles)) {
                    $cvFiles = [];
                }
            @endphp
            
            @if(!empty($cvFiles))
                @foreach($cvFiles as $cv)
                    <div class="d-flex align-items-center gap-3 p-2 bg-light rounded border border-gray-200">
                        <i class="bi bi-file-pdf text-danger fs-4"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $cv['original_name'] ?? 'CV File' }}</div>
                            <div class="text-muted fs-7">
                                @if(isset($cv['size']))
                                    {{ round($cv['size'] / 1024, 2) }} KB
                                @endif
                                @if(isset($cv['uploaded_at']))
                                    • Uploaded: {{ \Carbon\Carbon::parse($cv['uploaded_at'])->format('M d, Y H:i') }}
                                @endif
                            </div>
                        </div>
                        @if(isset($cv['url']))
                            <a href="{{ $cv['url'] }}" target="_blank" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        @elseif(isset($cv['path']))
                            <a href="{{ Storage::disk('public')->url($cv['path']) }}" target="_blank" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                        @endif
                    </div>
                @endforeach
            @elseif($formattedSeeker['cv_file_path'])
                <div class="d-flex align-items-center gap-3 p-2 bg-light rounded border border-gray-200">
                    <i class="bi bi-file-pdf text-danger fs-4"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">Primary CV</div>
                        <div class="text-muted fs-7">{{ basename($formattedSeeker['cv_file_path']) }}</div>
                    </div>
                    <a href="/admin/seekers/{{ $formattedSeeker['id'] }}/cv" target="_blank" class="btn btn-sm btn-primary">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                </div>
            @else
                <span class="text-muted">No CV files uploaded</span>
            @endif
        </div>
    </div>
</div>