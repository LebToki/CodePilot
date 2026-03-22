## 2024-05-24 - Form Accessibility
**Learning:** Found multiple instances where form labels (`<label>`) were not associated with their corresponding inputs (`<input>`) using the `for` and `id` attributes. Additionally, icon-only buttons for toggling password visibility lacked `aria-label` attributes.
**Action:** Always ensure that every form input has a unique `id` attribute and its corresponding label uses the `for` attribute referencing that `id`. For icon-only interactive elements, ensure an `aria-label` and `title` attribute are present.
## 2024-05-25 - File Tab Close Buttons
**Learning:** Found instances where interactive elements like file tab and close buttons within them (e.g. `<div class="close-btn">`) were not built with proper semantic elements (`<button>`) or ARIA attributes/keyboard interactions, making them difficult for screen readers to navigate.
**Action:** Always ensure that any interactive icon-only elements have proper `role="button"`, `tabindex="0"`, `onkeydown`, `aria-label`, and `title` attributes. Where possible, refactor interactive divs into semantic `<button>` tags without disrupting styling.
