# Product

Pintaria is an API-first Learning Management System inspired by Google Classroom. It supports classroom teaching, membership, forum posts/comments, materials, assignments, file attachments, student submissions, grading, and administrative monitoring.

## Roles

- **admin** — manages users and roles, views platform statistics, oversees all classrooms, and has administrative access.
- **guru** — teacher who creates and manages owned classrooms, posts materials/announcements, creates assignments, reviews submissions, and grades work.
- **siswa** — student who joins classrooms, participates in discussions, views assignments, submits work, and views personal grades.

## Assistant guidance

- Treat the REST API as the primary product surface; preserve JSON responses and existing endpoint semantics.
- Preserve the role boundaries and classroom ownership/membership checks. Do not weaken authorization to make a feature work.
- Use the existing English class/code naming conventions and the Indonesian role names (`guru`, `siswa`) used by the API.
- For uploads, follow Laravel Storage conventions and clean up stored files when deleting their owning records.
