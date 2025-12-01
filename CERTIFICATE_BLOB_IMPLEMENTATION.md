# Certificate BLOB Storage Implementation

## Overview
Implemented database BLOB storage for certificates to enable Heroku deployment compatibility.
Heroku's ephemeral filesystem loses files on dyno restarts, so storing PDFs in the database ensures persistence.

## Changes Made

### 1. Database Schema ( Completed)
- Added certificate_blob MEDIUMBLOB column to participants table
- Added aculty_certificate_blob MEDIUMBLOB column to programs table

### 2. Backend API Changes ( Completed)

#### Modified Endpoints:
1. **issue_certificate** - Line ~345
   - Changed from saving PDF to filesystem
   - Now captures PDF as binary string using $pdf->Output('S')
   - Stores binary data in certificate_blob column
   - Keeps certificate_file for filename reference only

2. **issue_faculty_certificate** - Line ~555
   - Same modifications as student certificates
   - Stores in aculty_certificate_blob column

3. **regenerate_certificate** - Line ~871
   - Updated to use BLOB storage instead of filesystem

4. **regenerate_faculty_certificate** - Line ~1000
   - Updated to use BLOB storage instead of filesystem

#### New Endpoints:
5. **get_certificate** (GET) - Line ~1016
   - Streams student certificate PDF from database
   - Sets proper Content-Type headers for PDF display
   - Returns 404 if certificate not found

6. **get_faculty_certificate** (GET) - Line ~1046
   - Streams faculty certificate PDF from database
   - Same header configuration as student endpoint

### 3. Frontend Changes ( Completed)

#### ADMIN/Certificates.html:
- Updated certificate viewing URLs to use streaming endpoints
- Changed from: ../certificates/certificate_{id}.pdf
- Changed to: ../backend/api.php?action=get_certificate&participant_id={id}
- Removed obsolete checkCertificateExists() function
- Certificates now open in new tab via streaming endpoint

## Testing Steps

1. **Generate a new certificate:**
   - Navigate to ADMIN/Certificates.html
   - Select a program with eligible participants
   - Click "Show Eligible Participants"
   - Click "Issue Certificate" for a student or faculty member

2. **Verify BLOB storage:**
   `sql
   SELECT id, certificate_file, LENGTH(certificate_blob) as blob_size 
   FROM participants 
   WHERE certificate_issued = 1 
   ORDER BY issued_on DESC 
   LIMIT 1;
   `

3. **View certificate:**
   - Click "View Certificate" in the issued certificates table
   - PDF should open in new browser tab
   - Check browser network tab: request should be to pi.php?action=get_certificate

4. **Test regeneration:**
   - Click "Regenerate" button for an existing certificate
   - Verify BLOB is updated in database
   - View certificate to confirm new version

## Heroku Deployment Notes

### Advantages:
-  Certificates persist across dyno restarts
-  No need for external storage service
-  Uses existing AWS RDS database
-  Simple implementation without additional dependencies

### Performance Considerations:
- MEDIUMBLOB supports up to 16MB (sufficient for PDF certificates)
- Average certificate size: ~50-100KB
- Database queries are fast with proper indexing
- Streaming responses handle large files efficiently

### Migration from Filesystem (if needed):
If you have existing certificates in certificates/ directory:
`php
// Migration script example
 = glob('certificates/*.pdf');
foreach ( as ) {
     = extractIdFromFilename();
     = file_get_contents();
     = ->prepare("UPDATE participants SET certificate_blob = ? WHERE id = ?");
    ->bind_param("si", , );
    ->execute();
}
`

## Rollback Plan (if needed)

To revert to filesystem storage:
1. Restore old pi.php endpoints (use git revert)
2. Restore old Certificates.html URLs
3. Keep BLOB columns in database (for future use)
4. Certificate files would need to be regenerated

## Date: 2025-12-01 21:41:19
