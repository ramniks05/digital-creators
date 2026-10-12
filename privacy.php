<!doctype html>
<html lang="en">
<head>
  <?php
  require_once __DIR__ . '/includes/seo.php';
  render_seo_head([
    'title' => 'Privacy Policy | Digital Creatorss',
    'description' => 'How Digital Creatorss collects, uses, and protects personal data including website forms and WhatsApp Business messaging.',
    'path' => 'privacy.php',
  ]);
  ?>
</head>
<body class="bg-bg-primary text-text-primary font-sans antialiased site-canvas">
  <div class="w-full min-h-screen relative bg-bg-primary">
    <?php include 'components/navbar.php'; ?>
    <?php
    $legalTitle = 'Privacy Policy';
    $legalUpdated = '12 October 2026';
    ob_start();
    ?>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">1. Who we are</h2>
      <p>Digital Creatorss (“we”, “us”) provides software development, web applications, cloud hosting, and related services. Our website is <strong>digitalcreatorss.com</strong>. Contact: <strong>info@digitalcreatorss.com</strong>.</p>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">2. Information we collect</h2>
      <ul class="list-disc pl-5 space-y-1">
        <li><strong>Website:</strong> name, email, phone, project details when you submit contact forms or enquiries.</li>
        <li><strong>WhatsApp Business:</strong> phone number, profile name (if provided by Meta), message content, media metadata, delivery/read status, and timestamps when you message our business number or we message you with your consent.</li>
        <li><strong>Technical:</strong> IP address, browser type, and cookies necessary for site operation and security.</li>
      </ul>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">3. How we use information</h2>
      <p>We use data to respond to enquiries, deliver services, operate our WhatsApp messaging portal, improve our website, comply with law, and prevent abuse. We do not sell your personal information.</p>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">4. WhatsApp & Meta</h2>
      <p>When you communicate via WhatsApp, your messages are processed by <strong>Meta Platforms</strong> under their terms and privacy policy, in addition to this policy. We store message content and metadata on our secure servers to manage customer support and project communication. You may opt out of WhatsApp marketing by telling us or ceasing to use that channel.</p>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">5. Legal basis & retention</h2>
      <p>We process data based on consent, contract performance, legitimate interest (support and security), and legal obligation. We retain data only as long as needed for the purposes above or as required by law, then delete or anonymise it.</p>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">6. Sharing</h2>
      <p>We may share data with hosting providers, email/communication tools, and Meta/WhatsApp as necessary to operate our services. We require appropriate safeguards where data is processed on our behalf.</p>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">7. Your rights</h2>
      <p>Depending on your location, you may request access, correction, deletion, restriction, or portability of your personal data, or withdraw consent. Contact us at <strong>info@digitalcreatorss.com</strong>. You may also lodge a complaint with your local data protection authority.</p>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">8. Security</h2>
      <p>We use technical and organisational measures including access controls, HTTPS, and secure configuration for APIs and databases. No method of transmission over the internet is 100% secure.</p>
    </section>
    <section>
      <h2 class="text-lg font-semibold text-text-primary mb-2">9. Changes</h2>
      <p>We may update this policy. The “Last updated” date will change when we do. Continued use of our services after changes constitutes acceptance where permitted by law.</p>
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
