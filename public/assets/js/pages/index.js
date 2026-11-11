// Loading screen: 5 seconds, then always the login page (server session is checked there).
// Hash-routed admin app: if the URL is /#/admin/..., the admin router takes over
// the page instead — do NOT leave, or the hash (and the admin panel) is lost.
if (location.hash.indexOf('#/admin') !== 0) {
  setTimeout(function () { location.replace('/login.php'); }, 5000);
}
