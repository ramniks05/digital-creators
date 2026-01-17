import { Link } from 'react-router-dom';
import { FaEnvelope, FaPhone, FaMapMarkerAlt, FaFacebook, FaTwitter, FaLinkedin, FaInstagram } from 'react-icons/fa';

const Footer = () => {
  const currentYear = new Date().getFullYear();

  const services = [
    'Software Development',
    'Website Development',
    'Web Applications',
    'SEO Services',
    'Digital Marketing',
    'Social Media Marketing'
  ];

  const quickLinks = [
    { path: '/', label: 'Home' },
    { path: '/about', label: 'About Us' },
    { path: '/services', label: 'Services' },
    { path: '/portfolio', label: 'Portfolio' },
    { path: '/contact', label: 'Contact' },
  ];

  return (
    <footer className="bg-gray-900 text-gray-300">
      <div className="container-custom section-padding">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          {/* Company Info */}
          <div>
            <h3 className="text-2xl font-bold text-white mb-4">
              Digital Creatorss
            </h3>
            <p className="mb-4 text-sm leading-relaxed">
              Digital Creatorss is an India-based software development and digital marketing company providing custom software, website development, and online marketing solutions.
            </p>
            <div className="flex space-x-4">
              <a
                href="https://facebook.com"
                target="_blank"
                rel="noopener noreferrer"
                className="text-gray-400 hover:text-blue-500 transition-colors"
                aria-label="Facebook"
              >
                <FaFacebook className="w-5 h-5" />
              </a>
              <a
                href="https://twitter.com"
                target="_blank"
                rel="noopener noreferrer"
                className="text-gray-400 hover:text-blue-400 transition-colors"
                aria-label="Twitter"
              >
                <FaTwitter className="w-5 h-5" />
              </a>
              <a
                href="https://linkedin.com"
                target="_blank"
                rel="noopener noreferrer"
                className="text-gray-400 hover:text-blue-600 transition-colors"
                aria-label="LinkedIn"
              >
                <FaLinkedin className="w-5 h-5" />
              </a>
              <a
                href="https://instagram.com"
                target="_blank"
                rel="noopener noreferrer"
                className="text-gray-400 hover:text-pink-500 transition-colors"
                aria-label="Instagram"
              >
                <FaInstagram className="w-5 h-5" />
              </a>
            </div>
          </div>

          {/* Quick Links */}
          <div>
            <h4 className="text-white font-semibold mb-4">Quick Links</h4>
            <ul className="space-y-2">
              {quickLinks.map((link) => (
                <li key={link.path}>
                  <Link
                    to={link.path}
                    className="hover:text-white transition-colors text-sm"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Services */}
          <div>
            <h4 className="text-white font-semibold mb-4">Our Services</h4>
            <ul className="space-y-2">
              {services.map((service, index) => (
                <li key={index}>
                  <Link
                    to="/services"
                    className="hover:text-white transition-colors text-sm"
                  >
                    {service}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Contact Info */}
          <div>
            <h4 className="text-white font-semibold mb-4">Contact Us</h4>
            <ul className="space-y-3">
              <li className="flex items-start space-x-3">
                <FaMapMarkerAlt className="w-5 h-5 text-blue-500 mt-1 flex-shrink-0" />
                <span className="text-sm">C-84, C-Block, Sec - 2, Noida, Uttar Pradesh, 201306</span>
              </li>
              <li className="flex items-start space-x-3">
                <FaPhone className="w-5 h-5 text-blue-500 mt-1 flex-shrink-0" />
                <div className="flex flex-col space-y-1">
                  <a
                    href="tel:+918851613806"
                    className="text-sm hover:text-white transition-colors"
                  >
                    +91 8851613806
                  </a>
                  <a
                    href="tel:+919311240888"
                    className="text-sm hover:text-white transition-colors"
                  >
                    +91 9311240888
                  </a>
                </div>
              </li>
              <li className="flex items-start space-x-3">
                <FaEnvelope className="w-5 h-5 text-blue-500 mt-1 flex-shrink-0" />
                <a
                  href="mailto:info@digitalcreatorss.com"
                  className="text-sm hover:text-white transition-colors break-all"
                >
                  info@digitalcreatorss.com
                </a>
              </li>
            </ul>
          </div>
        </div>

        {/* Copyright */}
        <div className="border-t border-gray-800 mt-12 pt-8 text-center text-sm">
          <p>
            &copy; {currentYear} Digital Creatorss. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
