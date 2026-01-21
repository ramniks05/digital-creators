import { useState } from 'react';
import { motion } from 'framer-motion';
import { FaLinkedin, FaTwitter, FaEnvelope } from 'react-icons/fa';

// Component to handle image loading with fallbacks
const TeamMemberImage = ({ member }) => {
  const [imageError, setImageError] = useState(false);
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  
  // Try different image formats
  const imageFormats = [
    member.image, // Original path
    member.image.replace('.jpg', '.jpeg'),
    member.image.replace('.jpg', '.png'),
    member.image.replace('.jpg', '.webp'),
    member.image.replace('.jpeg', '.jpg'),
    member.image.replace('.png', '.jpg'),
    member.image.replace('.webp', '.jpg'),
  ];

  const handleImageError = () => {
    if (currentImageIndex < imageFormats.length - 1) {
      setCurrentImageIndex(currentImageIndex + 1);
    } else {
      setImageError(true);
    }
  };

  if (imageError) {
    return (
      <div className="relative w-full flex items-center justify-center pt-6 pb-2">
        <div className="relative w-48 h-48 sm:w-56 sm:h-56 rounded-full overflow-hidden bg-gradient-to-br from-orange-500 to-blue-500 shadow-lg ring-4 ring-white flex items-center justify-center">
          <div className="text-center">
            <span className="text-white text-5xl sm:text-6xl font-bold">{member.name.charAt(0)}</span>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="relative w-full flex items-center justify-center pt-6 pb-2">
      <div className="relative w-48 h-48 sm:w-56 sm:h-56 rounded-full overflow-hidden bg-gradient-to-br from-orange-100 to-blue-100 shadow-lg ring-4 ring-white">
        <img
          src={imageFormats[currentImageIndex]}
          alt={member.name}
          className="w-full h-full object-cover object-center"
          style={{ 
            objectFit: 'cover',
            objectPosition: 'center top'
          }}
          loading="lazy"
          onError={handleImageError}
        />
      </div>
    </div>
  );
};

const Team = () => {
  // Team members data
  const teamMembers = [
    {
      id: 1,
      name: "Neekita Kumari",
      role: "MBA (IT) – Sales & Management",
      // Director image - PNG format
      image: "/director.png",
      bio: "Leading Digital Creatorss with vision and expertise in software development, sales, and management.",
      email: "director@digitalcretorss.com",
      phone: "+91 8851613806",
      linkedin: "#",
      twitter: "#"
    },
    // Add more team members here as needed
    // {
    //   id: 2,
    //   name: "Team Member Name",
    //   role: "Position",
    //   image: "/images/team/member1.jpg",
    //   bio: "Team member description",
    //   email: "member@digitalcreatorss.com",
    //   linkedin: "#",
    //   twitter: "#"
    // }
  ];

  return (
    <div className="w-full overflow-x-hidden">
      {/* Hero Section */}
      <section className="pt-20 sm:pt-24 md:pt-28 pb-8 sm:pb-10 md:pb-12 bg-gradient-to-br from-orange-50 via-blue-50 to-orange-50 overflow-x-hidden">
        <div className="container-custom">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
            className="text-center max-w-3xl mx-auto"
          >
            <h1 className="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-4 sm:mb-6 leading-tight break-words">
              Our <span className="bg-gradient-to-r from-orange-600 to-blue-600 bg-clip-text text-transparent break-words">Team</span>
            </h1>
            <p className="text-sm sm:text-base md:text-lg text-gray-600 leading-relaxed break-words">
              Meet the talented individuals behind Digital Creatorss who are dedicated to delivering exceptional digital solutions.
            </p>
          </motion.div>
        </div>
      </section>

      {/* Team Members Section */}
      <section className="section-padding bg-white">
        <div className="container-custom">
          {teamMembers.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
              {teamMembers.map((member, index) => (
                <motion.div
                  key={member.id}
                  initial={{ opacity: 0, y: 20 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.5, delay: index * 0.1 }}
                  className="bg-white rounded-lg shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden card-hover flex flex-col"
                >
                  {/* Team Member Image */}
                  <div className="flex-shrink-0">
                    <TeamMemberImage member={member} />
                  </div>

                  {/* Team Member Info */}
                  <div className="p-6">
                    <div className="text-center mb-4">
                      <h3 className="text-xl sm:text-2xl font-bold text-gray-900 mb-1">
                        {member.name}
                      </h3>
                      <p className="text-orange-600 font-semibold text-sm sm:text-base mb-3">
                        {member.role}
                      </p>
                      <p className="text-gray-600 text-sm leading-relaxed">
                        {member.bio}
                      </p>
                    </div>

                    {/* Contact Info */}
                    {(member.phone || member.email) && (
                      <div className="pt-4 border-t border-gray-200 space-y-2 mb-4">
                        {member.phone && (
                          <div className="flex items-center justify-center space-x-2 text-sm">
                            <span className="text-gray-600 font-semibold">Phone:</span>
                            <a href={`tel:${member.phone.replace(/\s/g, '')}`} className="text-orange-600 hover:text-orange-700 transition-colors font-medium">
                              {member.phone}
                            </a>
                          </div>
                        )}
                        {member.email && (
                          <div className="flex items-center justify-center space-x-2 text-sm">
                            <span className="text-gray-600 font-semibold">Email:</span>
                            <a href={`mailto:${member.email}`} className="text-orange-600 hover:text-orange-700 transition-colors break-all font-medium">
                              {member.email}
                            </a>
                          </div>
                        )}
                      </div>
                    )}

                    {/* Social Links */}
                    <div className="flex justify-center space-x-4 pt-4 border-t border-gray-200">
                      {member.email && (
                        <a
                          href={`mailto:${member.email}`}
                          className="w-10 h-10 bg-gray-100 hover:bg-orange-100 text-gray-600 hover:text-orange-600 rounded-full flex items-center justify-center transition-all duration-300"
                          aria-label="Email"
                        >
                          <FaEnvelope className="w-4 h-4" />
                        </a>
                      )}
                      {member.linkedin && member.linkedin !== "#" && (
                        <a
                          href={member.linkedin}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="w-10 h-10 bg-gray-100 hover:bg-blue-100 text-gray-600 hover:text-blue-600 rounded-full flex items-center justify-center transition-all duration-300"
                          aria-label="LinkedIn"
                        >
                          <FaLinkedin className="w-4 h-4" />
                        </a>
                      )}
                      {member.twitter && member.twitter !== "#" && (
                        <a
                          href={member.twitter}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="w-10 h-10 bg-gray-100 hover:bg-blue-100 text-gray-600 hover:text-blue-400 rounded-full flex items-center justify-center transition-all duration-300"
                          aria-label="Twitter"
                        >
                          <FaTwitter className="w-4 h-4" />
                        </a>
                      )}
                    </div>
                  </div>
                </motion.div>
              ))}
            </div>
          ) : (
            <div className="text-center py-12">
              <p className="text-gray-600 text-lg">No team members added yet.</p>
            </div>
          )}
        </div>
      </section>

      {/* Join Our Team Section */}
      <section className="section-padding bg-gradient-to-br from-orange-50 to-blue-50">
        <div className="container-custom text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Join Our Team
            </h2>
            <p className="text-sm sm:text-base md:text-lg text-gray-600 mb-6 max-w-2xl mx-auto">
              We're always looking for talented individuals to join our team. If you're passionate about technology and innovation, we'd love to hear from you.
            </p>
            <a
              href="/contact"
              className="btn-primary inline-block"
            >
              Get In Touch
            </a>
          </motion.div>
        </div>
      </section>
    </div>
  );
};

export default Team;
