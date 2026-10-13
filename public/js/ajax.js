/*
 * Reusable AJAX helper built on the native Fetch API (no libraries).
 *
 * ajaxRequest() never throws. It always resolves with:
 *   { ok, status, data, networkError }
 *   - ok:           true for any 2xx status
 *   - status:       HTTP status code (0 when the server could not be reached)
 *   - data:         parsed JSON body, or null if the body was empty or not JSON
 *   - networkError: true when the request never reached the server
 *
 * Each caller decides what counts as success for its own endpoint
 * (e.g. 201 for create, 200 for update/delete).
 */
async function ajaxRequest(url, options = {}) {
    const method = options.method || 'POST';
    const body = options.body ?? null;

    const headers = {
        'Accept': 'application/json',           // makes Laravel answer with JSON
        'X-Requested-With': 'XMLHttpRequest',
    };

    // CSRF token: passed in explicitly, or taken from the form's hidden @csrf input
    const csrfToken = options.csrfToken || (body instanceof FormData ? body.get('_token') : null);
    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    // FormData is sent as-is (the browser sets Content-Type); plain objects are sent as JSON
    let requestBody = body;
    if (body !== null && !(body instanceof FormData) && typeof body === 'object') {
        headers['Content-Type'] = 'application/json';
        requestBody = JSON.stringify(body);
    }

    let response;

    try {
        response = await fetch(url, { method: method, headers: headers, body: requestBody });
    } catch (error) {
        return { ok: false, status: 0, data: null, networkError: true };
    }

    // The body may be empty or not JSON (e.g. an HTML error page), so don't let parsing crash
    const text = await response.text().catch(function () {
        return '';
    });

    let data = null;
    try {
        data = text ? JSON.parse(text) : null;
    } catch (error) {
        data = null;
    }

    return { ok: response.ok, status: response.status, data: data, networkError: false };
}

/*
 * Turns a failed ajaxRequest() result into a message for the user.
 * Returns { text, withRefreshButton }.
 * `overrides.forbidden` lets each operation use its own 403 wording.
 */
function ajaxErrorMessage(result, overrides = {}) {
    if (result.networkError) {
        return { text: 'Could not reach the server. Check your internet connection and try again.', withRefreshButton: false };
    }

    if (result.status === 401 || result.status === 419) {
        return { text: 'Your session has expired. Please refresh the page (and log in again if asked), then try again.', withRefreshButton: true };
    }

    if (result.status === 403) {
        return { text: overrides.forbidden || 'You are not allowed to do that.', withRefreshButton: false };
    }

    if (result.status === 404) {
        return { text: 'This task no longer exists. Please refresh the page.', withRefreshButton: true };
    }

    return { text: 'Something went wrong on the server. Please try again later.', withRefreshButton: false };
}
