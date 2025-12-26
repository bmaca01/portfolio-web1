<!-- Mobile Toggle (checkbox hack for pure CSS collapsible menu) -->
<input type="checkbox" id="sidebar-toggle" class="sidebar-toggle-input">
<label for="sidebar-toggle" class="sidebar-toggle-btn">
  <font face="helvetica" size="3">☰ Menu</font>
</label>
<!-- Floating Sidebar Navigation Card -->
<div class="sidebar">
  <!-- Bio Section -->
  <!--
  <div class="sidebar-bio">
    <font face="helvetica">
      <strong class="sidebar-name">Ben Macaro</strong><br>
      <span class="sidebar-title">Full Stack Developer</span><br>
    </font>
  </div>
  -->

  <!-- Navigation Links -->
  <div class="sidebar-nav">
    <table width="100%" cellpadding="5" cellspacing="0" border="0">
      <tr>
        <td class="text-left">
          <a href="/index.php" class="nav-link <?php echo (isset($currentPage) && $currentPage === 'home') ? 'nav-link-active' : ''; ?>">▸ Home/About</a>
        </td>
      </tr>
      <tr>
        <td class="text-left">
          <a href="/portfolio.php" class="nav-link <?php echo (isset($currentPage) && $currentPage === 'portfolio') ? 'nav-link-active' : ''; ?>">▸ Projects/Skills</a>
        </td>
      </tr>
      <tr>
        <td class="text-left">
          <a href="/links.php" class="nav-link <?php echo (isset($currentPage) && $currentPage === 'links') ? 'nav-link-active' : ''; ?>">▸ Links</a>
        </td>
      </tr>
      <tr>
        <td class="text-left">
          <a href="/guestbook.php" class="nav-link <?php echo (isset($currentPage) && $currentPage === 'guestbook') ? 'nav-link-active' : ''; ?>">▸ Guestbook</a>
        </td>
      </tr>
      <tr>
        <td class="text-left">
          <a href="/blog/" class="nav-link <?php echo (isset($currentPage) && $currentPage === 'blog') ? 'nav-link-active' : ''; ?>">▸ Blog</a>
        </td>
      </tr>
      <tr>
        <td class="text-left">
          <a href="/contact.php" class="nav-link <?php echo (isset($currentPage) && $currentPage === 'contact') ? 'nav-link-active' : ''; ?>">▸ Contact</a>
        </td>
      </tr>
    </table>
  </div>

  <!-- Bottom Tagline -->
  <div class="sidebar-footer">
    <font face="helvetica" size="2">
    </font>
  </div>
</div>
