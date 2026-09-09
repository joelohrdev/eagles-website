---
paths:
  - 'resources/js/pages/**'
---

# Pages

## Function-style layout options receive page props, not the page
With Inertia v3 (3.7+), a page's `defineOptions({ layout: (...) => ({ breadcrumbs }) })` callback is called with `page.props` directly. Write `layout: (props: { order: Order }) => ({ ... props.order ... })`, never `(page) => page.props.order` — that form throws "Cannot read properties of undefined (reading 'order')" at render and blanks the admin page. See pages/admin/tryouts/Edit.vue for the correct shape; products/Edit.vue and orders/Show.vue were fixed on 2026-09-09.
