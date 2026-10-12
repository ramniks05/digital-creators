<!doctype html>
<html lang="en">
<head>
  <?php
  require_once __DIR__ . '/includes/seo.php';
  render_seo_head([
    'title' => 'Terms & Conditions | Digital Creatorss',
    'description' => 'Terms of use for Digital Creatorss website, services, and WhatsApp Business communication.',
    'path' => 'terms.php',
  ]);
  ?>
</head>
<body class="bg-bg-primary text-text-primary font-sans antialiased site-canvas">
  <div class="w-full min-h-screen relative bg-bg-primary">
    <?php include 'components/navbar.php'; ?>
    <?php
    $legalTitle = 'Terms & Conditions';
    $legalUpdated = '12 October 2026';
    ob_start();
    ?>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">1. Agreement</h2>
      <p>By using digitalcreatorss.com, contacting us, or messaging our WhatsApp Business number, you agree to these Terms. If you do not agree, please do not use our services.</p>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">2. Services</h2>
      <p>Digital Creatorss provides custom software, websites, mobile applications, cloud hosting, server management, and consulting. Specific scope, fees, and timelines are defined in separate proposals or contracts.</p>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">3. Website use</h2>
      <p>You must not misuse the site (unauthorised access, malware, scraping that harms performance, or unlawful content). Content on the site is our property or licensed to us unless stated otherwise.</p>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">4. WhatsApp messaging</h2>
      <ul class="list-disc pl-5 space-y-1">
        <li>You must provide accurate contact details and, where required, consent to receive WhatsApp messages from us.</li>
        <li>We use WhatsApp for support, project updates, and service-related communication in line with Meta’s WhatsApp Business policies.</li>
        <li>You must not use our WhatsApp channel for spam, harassment, or illegal content.</li>
        <li>Message delivery depends on Meta’s infrastructure; we are not liable for outages outside our control.</li>
      </ul>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">5. Intellectual property</h2>
      <p>Unless agreed in writing, deliverables and licences are governed by your project contract. Our pre-existing tools, frameworks, and branding remain our property.</p>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">6. Limitation of liability</h2>
      <p>To the fullest extent permitted by law, we are not liable for indirect or consequential loss. Our total liability for claims relating to the website or informal enquiries is limited to the amount you paid us for the relevant service in the twelve months before the claim, or INR 10,000 if no paid service applies, whichever is greater.</p>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">7. Indemnity</h2>
      <p>You agree to indemnify us against claims arising from your misuse of the site or WhatsApp channel or your breach of these Terms.</p>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">8. Governing law</h2>
      <p>These Terms are governed by the laws of India. Courts at Noida / Uttar Pradesh shall have exclusive jurisdiction, subject to mandatory consumer protections.</p>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">9. Contact</h2>
      <p>Questions: <strong>info@digitalcreatorss.com</strong>. See also our <a href="privacy.php" class="text-primary hover:underline">Privacy Policy</a>.</p>
    </section>
    <?php
    $legalBodyHtml = ob_get_clean();
    include 'components/legal-page-shell.php';
    ?>
    <?php include 'components/footer.php'; ?>
  </div>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });</script>
</body>
</html>
