<p>Hello {{ $user->name }},</p>

<p>Your requested StudyTracker report is ready.</p>

<p><strong>Months:</strong> {{ implode(', ', $months) }}</p>

<p>The report is attached as a CSV file.</p>

<p>Thanks,<br>StudyTracker</p>
