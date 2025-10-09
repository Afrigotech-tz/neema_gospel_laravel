# Department and Partner Implementation TODO

## Database Migrations
- [x] Create departments table migration
- [x] Create department_permissions pivot table migration
- [x] Add department_id, is_partner, partner_registration_method to users table
- [x] Add donation_type, manual_donation fields to donations table

## Models
- [x] Create Department model
- [x] Update User model with department and partner relationships
- [x] Update Permission model with department relationships
- [x] Update Donation model with manual donation support

## Controllers
- [x] Create DepartmentController for CRUD operations
- [ ] Update UserController for partner registration
- [ ] Update DonationController for manual donations

## API Routes
- [x] Add department routes to api.php
- [ ] Update user routes for partner functionality
- [ ] Update donation routes for manual donations

## Testing
- [x] Run migrations
- [ ] Test department creation and assignment
- [ ] Test partner registration (self and office)
- [ ] Test department-specific permissions
- [ ] Test manual donations
