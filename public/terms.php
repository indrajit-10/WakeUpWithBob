<?php
/**
 * Terms of Use  (  /terms.php  )  — friendly, plain-language house rules.
 */
require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';

ensure_session();

$updated = 'July 2026';   // ← update this date whenever you change the terms

$pageTitle = 'Terms · ' . SITE_NAME;
require __DIR__ . '/../app/views/header.php';
?>
<div class="app">
  <div class="shell">

    <nav class="left">
      <?php include __DIR__ . '/../app/views/leftnav.php'; ?>
    </nav>

    <main class="center">
      <div class="feedhead"><b>Terms of Use</b></div>

      <article class="post legal">
        <!-- <p class="legal-updated">Last updated: <?= e($updated) ?></p> -->
		<p class="legal-updated">Last updated: October 11, 2026</p>

        <p class="post-text">
			Welcome to Five Minutes with Bob (the <strong>“Site”</strong>), a daily reflective column and reader community available at human.123greetings.com. The Site is operated by and powered by 123Greetings.com, Inc. (<strong>“123Greetings,” “we,” “us,”</strong> or <strong>“our”</strong>). These Terms of Use (the <strong>“Terms”</strong>) are a legal agreement between you (<strong>“you”</strong> or <strong>“reader”</strong>) and 123Greetings and govern your access to and use of the Site. We have tried to keep them plain and readable, in the spirit of the Site itself, while still covering what needs to be covered. Please read them. They work alongside our Privacy Policy, which is incorporated here by reference. 
		</p>

        <h2>1. Your Acceptance of These Terms </h2>
        <p class="post-text">By visiting, reading, commenting on, or otherwise using the Site, you agree to be bound by these Terms. If you do not agree, please do not use the Site. We may revise these Terms from time to time; the date at the top shows when they were last updated. If we make a material change, we will post the updated Terms here and, if you subscribe to our emails, let you know by email. Changes apply only going forward, and your continued use of the Site after they take effect means you accept the revised Terms. </p>

        <h2>2. About Five Minutes with Bob </h2>
        <p class="post-text">Five Minutes with Bob is a free reading experience. Each morning, “Bob” shares a short reflective column, a question, a thought, or a small observation about the everyday rituals, memories, and moments that connect us. Readers may read the day's piece, browse the archive, share pieces with others, and leave comments that may appear publicly on the Site.</p>
		<p class="post-text">The columns are offered as reflection and gentle companionship, not as instruction. Bob does not promise formulas, outcomes, or answers. The Site is a place to pause and think, and the material is provided for general reading and enjoyment only.</p>

        <h2>3. Eligibility and Age</h2>
        <p class="post-text">The Site is not directed to children under the age of 13, and by using it you affirm that you are at least 13 years old. You must be at least 18 years old to post a Comment or to sign up for our emails. If you are in the European Economic Area or the United Kingdom, you must also be at least the minimum age of digital consent in your country, which in some places is older than 13. If you believe a child under 13 has submitted personal information to the Site, please contact us at <a href="mailto:bob@123greetings.com">bob@123greetings.com</a> and we will take appropriate steps to remove it.</p>

        <h2>4. No Account Needed</h2>
        <p class="post-text">You do not need to register or create an account to read the Site or to take part. You may be asked to provide a name or email address when you leave a comment so that we can review it, reply to you, or display an attribution; you are responsible for what you choose to share, and for making sure it is accurate and something you are comfortable making public.</p>

        <h2>5. Comments</h2>
        <p class="post-text">Any comment you post on the Site - together with any feedback you choose to send us (collectively, “Comments”) - is your responsibility. Comments are intended to be read by Bob and, once reviewed, may appear publicly for other readers. Please do not include anything you consider private or confidential, and remember that a comment may be published, in whole or in part, and may be edited for length or clarity before it appears. We will not change the meaning of a Comment that we publish under your name.</p>

        <h2>6. Being a Good Neighbour - Community Standards </h2>
        <p class="post-text">This is meant to be a kind and welcoming corner of the internet. When you take part, you agree that your Comments will not: </p>
		<ul>
			<li>harass, bully, threaten, demean, or intimidate any person, or promote violence or self-harm;</li>
			<li>contain hateful, abusive, obscene, sexually explicit, or otherwise objectionable material, or anything that sexualizes or endangers minors;</li>
			<li>be deceptive, impersonate any person or entity, or misrepresent your connection to anyone, including Bob or 123Greetings; </li>
			<li>infringe anyone's copyright, trademark, privacy, publicity, or other rights, or disclose another person's private information without their consent; </li>
			<li>be unlawful, or encourage others to break the law; </li>
			<li>be commercial in nature, including advertising, promotions, spam, or solicitation; or </li>
			<li>contain viruses or code intended to harm, disrupt, or gain unauthorized access to the Site or its users.</li>
		</ul>
		<p class="post-text">We are not obligated to monitor Comments, but we may, and we may decline or remove anything that does not fit the spirit of these Terms or the Site. </p>

        <h2>7. Your Comments and the License You Grant</h2>
        <p class="post-text">You keep ownership of your Comments. By posting a Comment, however, you grant 123Greetings a <strong>worldwide, non-exclusive, perpetual, irrevocable, royalty-free, sublicensable, and transferable license</strong> to use, store, reproduce, adapt, edit, publish, translate, display, and distribute that Comment - in whole or in part, and with or without attribution, in connection with the Site and across the other products, services, websites, and applications operated by 123Greetings and its affiliates, including for marketing and promotional purposes, in any media now known or later developed. This includes the right for Bob to respond to, quote, or build a column around a comment you post. You waive any moral rights in your Comments to the extent permitted by law. We will not use your name or likeness alongside your Comment in advertising or marketing for other 123Greetings products or services without your separate, express consent. Nothing in this Section limits any rights you have under applicable data protection law regarding personal information contained in your Comment.</p>

        <h2>8. Your Representations and Warranties</h2>
        <p class="post-text">For every Comment, you represent and warrant that: (a) it is your own, or you otherwise have all rights necessary to post it and to grant the license above; (b) it does not infringe or violate the rights of any third party; (c) you have the consent of any identifiable person you mention or describe; and (d) it complies with these Terms and applicable law.</p>
		
		<h2>9. Moderation and Removal</h2>
        <p class="post-text">We may review Comments before they are published. We may, at our sole discretion and without any obligation or notice, approve, decline, edit, shorten, or remove any Comment, and we may withhold or take down comments for any reason or no reason. Publication of a Comment is never guaranteed, and reviewing Comments does not make us responsible for them. If you see something that troubles you, you can let us know at <a href="mailto:bob@123greetings.com">bob@123greetings.com</a>.</p>
		
		<h2>10. Nature of the Content – Reflection, Not Professional Advice </h2>
        <p class="post-text">Five Minutes with Bob offers personal reflection and general reading on themes such as everyday life, relationships, memory, and personal growth. It is not professional advice of any kind, and it is not a substitute for the guidance of a qualified professional. Nothing on the Site should be relied upon as medical, psychological, mental-health, legal, financial, or other professional advice, and no reader-and-author or professional relationship is created by your use of the Site.</p>
		
		<p class="post-text">If you are struggling or in distress, please reach out to a qualified professional or a trusted person in your life. If you may be in crisis or think someone else may be, contact your local emergency services or a crisis helpline in your area. The Site and Bob cannot provide emergency help.</p>
		
		<h2>11. Intellectual Property Rights</h2>
        <p class="post-text">The Site and its content - including Bob's columns and questions, together with the text, artwork, design, logos, and the selection, arrangement, and overall look and feel of the Site are owned by or licensed to 123Greetings and are protected by copyright, trademark, and other laws. Except for your own Comments, and except as the Site's ordinary features allow (such as sharing a link to a column), you may not copy, reproduce, republish, distribute, sell, or commercially exploit any part of the Site without our prior written permission. All rights not expressly granted are reserved.</p>
		
		<h2>12. Copyright Complaints (DMCA)</h2>
        <p class="post-text">123Greetings respects the intellectual property of others. In accordance with the Digital Millennium Copyright Act (DMCA), we will respond to proper notices of alleged copyright infringement and, where appropriate, remove the material in question. If you believe content on the Site infringes your copyright, please send a written notice to our Copyright Agent that includes your signature, identification of the copyrighted work and of the material you say is infringing (with enough detail to locate it), your contact information, a statement of your good-faith belief that the use is not authorized, and a statement under penalty of perjury that your notice is accurate and that you are authorized to act for the owner.</p>
		
		<p class="post-text">Send copyright notices to our Copyright Agent at <strong><a href="mailto:copyright@123greetings.com">copyright@123greetings.com</a></strong>, or <br>by mail to 123Greetings.com, Inc., <br>Attn: Copyright Agent, 4052 S 124th Street, Greenfield, WI 53228.</p>
		
		<p class="post-text"><strong>Counter-notices.</strong> If you believe material you posted was removed by mistake or misidentification, you may send our Copyright Agent a counter-notice that includes your physical or electronic signature; identification of the material that was removed and where it appeared before removal; a statement under penalty of perjury that you have a good-faith belief the material was removed as a result of mistake or misidentification; your name, address, and telephone number; and a statement that you consent to the jurisdiction of the federal district court for the judicial district in which your address is located (or, if you are outside the United States, any judicial district in which 123Greetings may be found) and that you will accept service of process from the person who provided the original notice. We may restore the removed material as permitted by the DMCA unless the person who sent the original notice informs us that they have filed a court action.</p>
		
		<p class="post-text"><strong>Repeat infringers.</strong> In appropriate circumstances, we will block Comments from, and terminate the access of, readers who repeatedly infringe the copyrights of others.</p>
		
		<h2>13. Links, Sharing, and Third-Party Services</h2>
        <p class="post-text">The Site may let you share columns through third-party services (such as messaging apps) and may contain links to other 123Greetings sites or to third-party websites. Those other sites and services are governed by their own terms and privacy policies, and we are not responsible for their content or practices. Your use of them is at your own risk.</p>
		
		<h2>14. Email Subscriptions </h2>
        <p class="post-text">You may choose to sign up to receive Five Minutes with Bob by email, so that each day's column, occasional related messages about the Site, and promotional messages about other 123Greetings products and services reach your inbox. Signing up is voluntary: you give us your email address for these purposes, and by doing so you consent to receiving these emails from us. Where the law in your country requires a separate consent for marketing messages, we will ask for it when you sign up.</p>
		
		<p class="post-text">You can unsubscribe at any time using the link included in every email we send, or by contacting us at <a href="mailto:bob@123greetings.com">bob@123greetings.com</a>, and we will stop sending you the daily column promptly. We may still send you the occasional message needed to manage your subscription, such as confirming a change you have asked for. We send these emails only to addresses that have been provided to us for this purpose, and we do not sell your email address.</p>
		
		<p class="post-text">How we handle your email address is also covered by our Privacy Policy. We use it to deliver the column and to understand whether our emails are opened and read so that we can improve them. To do this, our emails may contain small tracking technologies that tell us whether and when an individual email is opened. Where the law requires your consent for this, we will ask for it. Please make sure the address you give us is your own and one you are happy to receive mail at.</p>
		
		<h2>15. Privacy </h2>
        <p class="post-text">Your use of the Site is also governed by our Privacy Policy, which explains what information we collect and how we use it. By using the Site, you agree to those practices. Please remember that any information you include in a published comment may be seen by others.</p>
		
		<h2>16. The Site Is Provided “As Is”</h2>
        <p class="post-text"><strong>TO THE FULLEST EXTENT PERMITTED BY LAW, THE SITE AND ALL CONTENT ARE PROVIDED ON AN “AS IS” AND “AS AVAILABLE” BASIS, WITHOUT WARRANTIES OF ANY KIND, WHETHER EXPRESS OR IMPLIED, INCLUDING WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, TITLE, AND NON INFRINGEMENT. WE DO NOT WARRANT THAT THE SITE WILL BE UNINTERRUPTED, SECURE, ERROR-FREE, OR AVAILABLE AT ANY PARTICULAR TIME, OR THAT IT WILL BE FREE OF VIRUSES OR OTHER HARMFUL COMPONENTS. YOU USE THE SITE AT YOUR OWN RISK.</strong></p>
		
		<h2>17. Limitation of Liability</h2>
        <p class="post-text"><strong>TO THE FULLEST EXTENT PERMITTED BY LAW, 123GREETINGS AND ITS OFFICERS, DIRECTORS, EMPLOYEES, AGENTS, AND LICENSORS WILL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, EXEMPLARY, OR PUNITIVE DAMAGES, OR FOR ANY LOSS OF DATA, GOODWILL, OR OTHER INTANGIBLE LOSSES, ARISING OUT OF OR RELATING TO YOUR USE OF, OR INABILITY TO USE, THE SITE OR ANY CONTENT OR COMMENTS, UNDER ANY LEGAL THEORY. TO THE FULLEST EXTENT PERMITTED BY LAW, OUR TOTAL AGGREGATE LIABILITY ARISING OUT OF OR RELATING TO THE SITE OR THESE TERMS WILL NOT EXCEED ONE HUNDRED U.S. DOLLARS (US $100). SOME JURISDICTIONS DO NOT ALLOW CERTAIN LIMITATIONS, SO SOME OF THE ABOVE MAY NOT APPLY TO YOU.</strong></p>
		
		<h2>18. Indemnification</h2>
        <p class="post-text">You agree to defend, indemnify, and hold harmless 123Greetings and its officers, directors, employees, agents, and licensors from and against any claims, damages, losses, liabilities, costs, and expenses (including reasonable attorneys' fees) arising out of or relating to your Comments, your use of the Site, your violation of these Terms, or your violation of any rights of a third party.</p>
		
		<h2>19. Suspension and Termination</h2>
        <p class="post-text">We may limit, suspend, or end your access to all or part of the Site at any time, with or without cause and with or without notice. You may stop using the Site whenever you like. The provisions of these Terms, that by their nature should survive including the license you grant for your Comments, intellectual property, disclaimers, limitation of liability, indemnification, and governing law will continue to apply.</p>
		
		<h2>20. Changes to the Site and These Terms</h2>
        <p class="post-text">We may change, pause, or discontinue the Site, or any part of it, at any time without notice or liability. We may also update these Terms as described in Section 1. Please check back from time to time so you know the current Terms.</p>
		
		<h2>21. Governing Law and Jurisdiction</h2>
        <p class="post-text">These Terms are governed by the internal substantive laws of the State of New York, without regard to its conflict-of-laws principles. Any claim or dispute between you and 123Greetings arising in whole or in part out of the Site or these Terms will be decided exclusively by a federal or state court of competent jurisdiction located in New York, and you consent to the personal jurisdiction and venue of those courts.</p>
		
		<h2>22. International Use </h2>
        <p class="post-text">We make no representation that the Site is appropriate or available for use in every location. If you access the Site from outside the United States, you do so on your own initiative and are responsible for compliance with your local laws. If you access the Site from the European Economic Area or the United Kingdom, our collection and use of your personal information is described in our Privacy Policy, including the legal bases we rely on and the rights available to you under the EU General Data Protection Regulation (GDPR) and the UK GDPR.</p>
		
		<h2>23. General </h2>
        <p class="post-text">These Terms, together with the Privacy Policy, are the entire agreement between you and 123Greetings regarding the Site. If any part of these Terms is found unenforceable, the rest remains in effect. Our not enforcing a provision is not a waiver of it. You may not transfer your rights under these Terms; we may transfer ours. Headings are for convenience only.</p>
		
		<h2>24. Contact Us </h2>
        <p class="post-text">We would love to hear from you. For anything about the Site or these Terms, reach us at: </p>
		
		<p class="post-text">
			<strong>Five Minutes with Bob - <a href="mailto:bob@123greetings.com">bob@123greetings.com</a><br>
			123Greetings.com, Inc.</strong><br>
			4052 S 124th Street, Greenfield, WI 53228, USA<br>
			Copyright Agent: <a href="mailto:copyright@123greetings.com">copyright@123greetings.com</a>
		</p>
		
      </article>
    </main>

    <aside class="right">
      <?php include __DIR__ . '/../app/views/sidebar.php'; ?>
    </aside>

  </div>
</div>
<?php require __DIR__ . '/../app/views/footer.php'; ?>
