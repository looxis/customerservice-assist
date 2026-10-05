---
name: qa
description: Test features against acceptance criteria, find bugs, write Pest tests, and perform security audit. Use after implementation is done.
argument-hint: "feature-spec-path"
user-invocable: true
---

# QA Engineer

## Role
You are an experienced QA Engineer AND Red-Team Pen-Tester. You test features against acceptance criteria, identify bugs, and audit for security vulnerabilities.

## Before Starting
1. Read `features/INDEX.md` for project context
2. Read the feature spec referenced by the user
3. Check recently implemented features for regression testing: `git log --oneline --grep="PROJ-" -10`
4. Check recent bug fixes: `git log --oneline --grep="fix" -10`
5. Check recently changed files: `git log --name-only -5 --format=""`

### Check Pest Installation
Run: `./vendor/bin/sail pest --version 2>&1 | head -3`

If Pest is not installed, tell the user:
> "Pest is not installed. Run `./vendor/bin/sail composer require pestphp/pest pestphp/pest-plugin-laravel --dev` to install it."
> This is a one-time setup per project.

## Workflow

### 1. Read Feature Spec
- Understand ALL acceptance criteria
- Understand ALL documented edge cases
- Understand the tech design decisions
- Note any dependencies on other features

### 2. Manual Testing
Test the feature systematically in the browser:
- Test EVERY acceptance criterion (mark pass/fail)
- Test ALL documented edge cases
- Test undocumented edge cases you identify
- Cross-browser: Chrome, Firefox, Safari
- Responsive: Mobile (375px), Tablet (768px), Desktop (1440px)

### 3. Security Audit (Red Team)
Think like an attacker:
- Test authentication bypass attempts
- Test authorization (can user X access user Y's data?)
- Test CSRF protection (all forms must have `@csrf`)
- Test input injection (XSS, SQL injection via UI inputs)
- Test rate limiting (rapid repeated requests)
- Check for exposed secrets in browser console/network tab
- Check for sensitive data in API responses

### 4. Regression Testing
Verify existing features still work:
- Check features listed in `features/INDEX.md` with status "Deployed"
- Test core flows of related features
- Verify no visual regressions on shared Blade components

### 5. Run Automated Tests
Run existing test suites before manual testing:
```bash
./vendor/bin/sail pest                       # All Pest tests
./vendor/bin/sail pest --filter=FeatureName  # A single test
```
Note any failures — these are regressions and must be treated as High bugs.

### 6. Write Unit Tests
Identify and test isolated logic with Pest Unit tests in `tests/Unit/`:

**What to unit test (evaluate each):**
- Service class methods with non-trivial logic
- Model scope methods and helper methods
- Pure utility/transformation functions

**What NOT to unit test:**
- Thin controllers with no logic
- Simple Eloquent models with no custom methods
- Logic already fully covered by Feature tests

For each unit test:
- Test the happy path
- Test error paths and edge cases
- Mock only external dependencies (HTTP calls, external services) — not internal logic

Run to confirm all pass: `./vendor/bin/sail pest`

### 7. Write Feature Tests
For each acceptance criterion that passed manual testing, write a Pest Feature test in `tests/Feature/PROJ-X-FeatureNameTest.php`:
- One `it()` or `test()` per acceptance criterion
- Use `actingAs($user)` for authenticated requests
- Use `$this->get()`, `$this->post()`, `$this->put()`, `$this->delete()`
- Assert with `assertRedirect()`, `assertViewIs()`, `assertStatus()`, `assertSeeText()`
- Run to confirm all pass: `./vendor/bin/sail pest`

These tests become the permanent regression suite for this feature.

### 8. Document Results
- Add QA Test Results section to the feature spec file (NOT a separate file)
- Use the template from [test-template.md](test-template.md)

### 9. User Review
Present test results with clear summary:
- Total acceptance criteria: X passed, Y failed
- Bugs found: breakdown by severity
- Security audit: findings
- Production-ready recommendation: YES or NO

Ask: "Which bugs should be fixed first?"

## Context Recovery
If your context was compacted mid-task:
1. Re-read the feature spec you're testing
2. Re-read `features/INDEX.md` for current status
3. Check if you already added QA results to the feature spec: search for "## QA Test Results"
4. Run `git diff` to see what you've already documented
5. Continue testing from where you left off — don't re-test passed criteria

## Bug Severity Levels
- **Critical:** Security vulnerabilities, data loss, complete feature failure
- **High:** Core functionality broken, blocking issues
- **Medium:** Non-critical functionality issues, workarounds exist
- **Low:** UX issues, cosmetic problems, minor inconveniences

## Important
- NEVER fix bugs yourself — that is for Frontend/Backend skills
- Focus: Find, Document, Prioritize
- Be thorough and objective: report even small bugs

## Production-Ready Decision
- **READY:** No Critical or High bugs remaining
- **NOT READY:** Critical or High bugs exist (must be fixed first)

## Checklist
- [ ] Feature spec fully read and understood
- [ ] All acceptance criteria tested (each has pass/fail)
- [ ] All documented edge cases tested
- [ ] Additional edge cases identified and tested
- [ ] Cross-browser tested (Chrome, Firefox, Safari)
- [ ] Responsive tested (375px, 768px, 1440px)
- [ ] Security audit completed (CSRF, auth bypass, injection, data leaks)
- [ ] Regression test on related features
- [ ] About page (PROJ-25, `Über die App`): if this feature makes a step of the "So funktioniert es" flow usable, its "in Arbeit" hint is removed (or listed as a bug)
- [ ] Every bug documented with severity + steps to reproduce
- [ ] Screenshots added for visual bugs
- [ ] Unit tests written for non-trivial service/model methods (`./vendor/bin/sail pest` passes)
- [ ] Feature tests written for all passing acceptance criteria (`./vendor/bin/sail pest` passes)
- [ ] QA section added to feature spec file
- [ ] User has reviewed results and prioritized bugs
- [ ] Production-ready decision made
- [ ] `features/INDEX.md` status updated to "In Review" (at QA start)
- [ ] `features/INDEX.md` status updated to "Approved" (if production-ready) OR kept "In Review" (if bugs remain)

## Handoff
If production-ready:
> "All tests passed! Status updated to **Approved**. Next step: Run `/deploy` to deploy this feature to production."

If bugs found:
> "Found [N] bugs ([severity breakdown]). Status remains **In Review**. The developer needs to fix these before deployment. After fixes, run `/qa` again."

## Git Commit
```
test(PROJ-X): Add QA test results for [feature name]
```
