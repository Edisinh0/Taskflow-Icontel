# Quick Start Guide - Task Creation Feature

## 🚀 Getting Started

### For Users

1. **Navigate to Dashboard**
   - Click "Nueva Tarea" button in the top-right corner

2. **Fill Task Details**
   - **Título** (required): Task name
   - **Descripción** (required): Task details
   - **Tipo de Vínculo** (required): Select "Caso" or "Oportunidad"

3. **Search & Select Parent**
   - Type at least 2 characters to search
   - Wait for results (300ms debounce)
   - Click desired case/opportunity from dropdown

4. **Select Assignee & Priority**
   - **Responsable** (required): Pick team member
   - **Prioridad** (required): Low/Medium/High/Urgent

5. **Create**
   - Click "Crear Tarea" button
   - Modal closes, task appears in your dashboard

---

## 🔧 For Developers

### Testing the Backend

**Test Search Endpoint**:
```bash
curl -X GET "http://localhost/api/v1/crm/search-entities?module=Cases&query=test&limit=10" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Expected Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": "case-123",
      "name": "Test Case",
      "case_number": "1234",
      "status": "Open",
      "priority": "High"
    }
  ],
  "meta": {
    "module": "Cases",
    "query": "test",
    "count": 1
  }
}
```

**Test Task Creation**:
```bash
curl -X POST "http://localhost/api/v1/tasks" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Follow up with client",
    "description": "Get feedback on proposal",
    "sweetcrm_parent_type": "Cases",
    "sweetcrm_parent_id": "case-123",
    "assignee_id": 1,
    "priority": "high"
  }'
```

**Expected Response** (201 Created):
```json
{
  "success": true,
  "message": "Tarea creada exitosamente",
  "data": {
    "id": 456,
    "title": "Follow up with client",
    "case_id": 12,
    "priority": "high",
    "status": "pending",
    "created_by": 1,
    "crmCase": { ... },
    "assignee": { ... }
  }
}
```

### Testing the Frontend

**Manual Testing**:
1. Open browser DevTools (F12)
2. Go to Dashboard
3. Click "Nueva Tarea" button
4. Type in search field (watch Network tab for API calls)
5. Verify results appear with 300ms delay
6. Try creating a task with invalid parent (should show error)
7. Create a valid task (should close modal + refresh)

**Console Debugging**:
```javascript
// In browser console on DashboardView
tasksStore.searchCrmEntities('Cases', 'test', 10)
// Should return Promise with results

// Check modal state
console.log(showTaskCreationModal.value)  // true/false
```

---

## 🎯 Common Scenarios

### Scenario 1: User Searches for Cases

**User Action**: Types "proyecto" in search field
**Backend**: Queries SuiteCRM for Cases with name LIKE "%proyecto%"
**Frontend**: Displays results in dropdown after 300ms debounce
**User Can**: Click to select, see case details (number, status)

### Scenario 2: User Creates Task for Opportunity

**User Action**:
1. Selects "Oportunidad" type
2. Searches and selects opportunity
3. Fills other required fields
4. Clicks "Crear Tarea"

**Backend**:
1. Validates all required fields
2. Validates opportunity exists via TaskParentValidationService
3. Links task to opportunity via opportunity_id
4. Creates task with created_by = current user

**Result**: Task created, modal closes, dashboard refreshes

### Scenario 3: User Encounters Error

**User Action**: Searches for case that doesn't exist
**Frontend**: Shows "No se encontraron resultados para 'xyz'"

**User Action**: Tries to create task without assignee
**Frontend**: Submit button stays disabled, shows validation message

**User Action**: Selects invalid parent
**Backend**: Returns 422 error "El caso padre con ID 'xyz' no existe"
**Frontend**: Displays error in modal, user can retry

---

## 📋 Troubleshooting

### Problem: Modal doesn't open

**Check**:
- Browser console for JavaScript errors
- Import statement in DashboardView
- `showTaskCreationModal` ref is initialized

**Fix**:
- Clear browser cache (Ctrl+Shift+Delete)
- Hard refresh (Ctrl+F5)
- Check for console errors

### Problem: Search returns no results

**Check**:
- Query is >= 2 characters
- SuiteCRM credentials are correct
- Database actually contains matching records

**Fix**:
- Verify .env has valid SWEETCRM_* values
- Check SuiteCRM directly for matching Cases/Opportunities
- Test API endpoint manually with curl

### Problem: Task creation fails

**Check**:
- All required fields are filled
- Assignee exists in system
- Parent case/opportunity actually exists

**Fix**:
- Check error message in modal
- Verify parent_id is correct
- Test API endpoint with curl/Postman

### Problem: Modal closes but task doesn't appear

**Check**:
- Check browser Network tab for POST response
- Verify 201 status code received
- Check browser console for errors

**Fix**:
- Refresh page manually
- Check database directly for task creation
- Check application logs for errors

---

## 🔐 Security Notes

✅ **SQL Injection**: Query is sanitized via `str_replace("'", "\'", $query)`
✅ **Authorization**: All endpoints protected by `auth:sanctum`
✅ **Validation**: Laravel validates all inputs before processing
✅ **Session**: Uses cached SuiteCRM sessions (1-hour TTL)

⚠️ **Do Not**:
- Bypass Gate authorization check
- Store user credentials in localStorage
- Expose API tokens in frontend code
- Skip parent validation

---

## 📊 Performance Tips

**For Users**:
- Type slowly (wait for results)
- Don't create too many tabs
- Clear browser cache periodically

**For Developers**:
- Monitor 300ms debounce in Network tab
- Check database query performance
- Limit search results to 50 max
- Use proper indexing on name columns

---

## 🔗 Related Files

**Backend**:
- `app/Services/SweetCrmService.php` - Search implementation
- `app/Http/Controllers/Api/CrmSearchController.php` - Search controller
- `app/Http/Controllers/Api/TaskController.php` - Task creation
- `app/Services/TaskParentValidationService.php` - Parent validation

**Frontend**:
- `src/components/TaskCreationModal.vue` - Modal component
- `src/stores/tasks.js` - State management
- `src/views/DashboardView.vue` - Integration point

---

## 📞 Support

- **Backend Issues**: Check `storage/logs/laravel.log`
- **Frontend Issues**: Check browser DevTools console
- **SuiteCRM Issues**: Check SuiteCRM logs directly
- **Documentation**: See `TASK_CREATION_IMPLEMENTATION.md`

---

## ✅ Final Checklist Before Deployment

- [ ] Backend PHP syntax verified
- [ ] Frontend Vue component renders correctly
- [ ] Search endpoint returns valid results
- [ ] Task creation validates parent
- [ ] Modal opens/closes properly
- [ ] Error messages display correctly
- [ ] Dark mode styling works
- [ ] Responsive design works on mobile
- [ ] All required dependencies installed
- [ ] Cache cleared if needed
- [ ] Environment variables set correctly

---

**Last Updated**: 2026-01-09
**Version**: 1.0
**Status**: Production Ready ✅
