import { useParams, Link, useNavigate } from 'react-router-dom';
import { motion } from 'framer-motion';
import { FaArrowLeft, FaCalendar, FaTag, FaCode, FaCheckCircle, FaExternalLinkAlt } from 'react-icons/fa';
import { portfolioData } from '../data/portfolioData';

const ProjectDetails = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const project = portfolioData.find(p => p.id === parseInt(id));

  if (!project) {
    return (
      <div className="w-full overflow-x-hidden min-h-screen flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-3xl font-bold text-gray-900 mb-4">Project Not Found</h1>
          <p className="text-gray-600 mb-6">The project you're looking for doesn't exist.</p>
          <Link to="/portfolio" className="btn-primary">
            Back to Portfolio
          </Link>
        </div>
      </div>
    );
  }

  // Extended project details
  const projectDetails = {
    ...project,
    fullDescription: project.description + " This project showcases our expertise in delivering high-quality solutions that meet client requirements and exceed expectations. We focused on creating a user-friendly interface, optimizing performance, and ensuring scalability for future growth.",
    features: [
      "Responsive Design",
      "Fast Loading Times",
      "SEO Optimized",
      "User-Friendly Interface",
      "Cross-Browser Compatible",
      "Mobile-First Approach"
    ],
    client: "Confidential Client",
    date: "2024",
    duration: "2-3 months"
  };

  return (
    <div className="w-full overflow-x-hidden">
      {/* Back Button */}
      <section className="pt-20 sm:pt-24 md:pt-28 pb-4 bg-white">
        <div className="container-custom">
          <motion.button
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            onClick={() => navigate(-1)}
            className="flex items-center space-x-2 text-gray-600 hover:text-orange-600 transition-colors mb-6"
          >
            <FaArrowLeft className="w-4 h-4" />
            <span className="font-medium">Back to Portfolio</span>
          </motion.button>
        </div>
      </section>

      {/* Hero Image */}
      <section className="relative h-64 sm:h-80 md:h-96 lg:h-[500px] overflow-hidden">
        <img
          src={project.image}
          alt={project.title}
          className="w-full h-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
        <div className="absolute bottom-0 left-0 right-0 container-custom pb-8">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
          >
            <span className="inline-block px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-full mb-4">
              {project.category}
            </span>
            <h1 className="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4 leading-tight">
              {project.title}
            </h1>
          </motion.div>
        </div>
      </section>

      {/* Project Details */}
      <section className="section-padding bg-white">
        <div className="container-custom">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
            {/* Main Content */}
            <div className="lg:col-span-2">
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.6 }}
              >
                <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">Project Overview</h2>
                <p className="text-gray-600 mb-6 leading-relaxed text-sm sm:text-base">
                  {projectDetails.fullDescription}
                </p>

                <h3 className="text-xl sm:text-2xl font-bold text-gray-900 mb-4 mt-8">Key Features</h3>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                  {projectDetails.features.map((feature, index) => (
                    <div key={index} className="flex items-center space-x-3">
                      <FaCheckCircle className="w-5 h-5 text-green-500 flex-shrink-0" />
                      <span className="text-gray-700 text-sm sm:text-base">{feature}</span>
                    </div>
                  ))}
                </div>

                <h3 className="text-xl sm:text-2xl font-bold text-gray-900 mb-4">Technologies Used</h3>
                <div className="flex flex-wrap gap-2 mb-6">
                  {project.technologies.map((tech, idx) => (
                    <span
                      key={idx}
                      className="px-4 py-2 bg-gradient-to-r from-orange-100 to-blue-100 text-orange-700 font-semibold rounded-lg text-sm sm:text-base"
                    >
                      {tech}
                    </span>
                  ))}
                </div>
              </motion.div>
            </div>

            {/* Sidebar */}
            <div className="lg:col-span-1">
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.6, delay: 0.2 }}
                className="bg-gradient-to-br from-orange-50 to-blue-50 p-6 rounded-lg sticky top-24"
              >
                <h3 className="text-xl font-bold text-gray-900 mb-6">Project Information</h3>
                
                <div className="space-y-4">
                  <div className="flex items-start space-x-3">
                    <FaTag className="w-5 h-5 text-orange-600 mt-1 flex-shrink-0" />
                    <div>
                      <p className="text-sm text-gray-600">Category</p>
                      <p className="font-semibold text-gray-900">{project.category}</p>
                    </div>
                  </div>

                  <div className="flex items-start space-x-3">
                    <FaCalendar className="w-5 h-5 text-orange-600 mt-1 flex-shrink-0" />
                    <div>
                      <p className="text-sm text-gray-600">Year</p>
                      <p className="font-semibold text-gray-900">{projectDetails.date}</p>
                    </div>
                  </div>

                  <div className="flex items-start space-x-3">
                    <FaCode className="w-5 h-5 text-orange-600 mt-1 flex-shrink-0" />
                    <div>
                      <p className="text-sm text-gray-600">Duration</p>
                      <p className="font-semibold text-gray-900">{projectDetails.duration}</p>
                    </div>
                  </div>
                </div>

                <div className="mt-8 pt-6 border-t border-gray-300">
                  <h4 className="font-semibold text-gray-900 mb-3">Client</h4>
                  <p className="text-gray-600 text-sm">{projectDetails.client}</p>
                </div>

                {project.link && project.link !== "#" && (
                  <a
                    href={project.link}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="btn-primary w-full mt-6 flex items-center justify-center space-x-2"
                  >
                    <span>Visit Live Site</span>
                    <FaExternalLinkAlt className="w-4 h-4" />
                  </a>
                )}
              </motion.div>
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="section-padding bg-gradient-to-r from-orange-600 to-blue-600 text-white">
        <div className="container-custom text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold mb-4">
              Interested in a Similar Project?
            </h2>
            <p className="text-base sm:text-lg mb-6 max-w-2xl mx-auto opacity-90">
              Let's discuss how we can help bring your vision to life
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link
                to="/contact"
                className="bg-white text-orange-600 px-6 sm:px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1 inline-block"
              >
                Get Started
              </Link>
              <Link
                to="/portfolio"
                className="bg-transparent border-2 border-white text-white px-6 sm:px-8 py-3 rounded-lg font-semibold hover:bg-white/10 transition-all duration-300 inline-block"
              >
                View More Projects
              </Link>
            </div>
          </motion.div>
        </div>
      </section>
    </div>
  );
};

export default ProjectDetails;
