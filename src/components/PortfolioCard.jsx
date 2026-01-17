import { motion } from 'framer-motion';
import { FaExternalLinkAlt } from 'react-icons/fa';

const PortfolioCard = ({ project, index }) => {
  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true }}
      transition={{ duration: 0.5, delay: index * 0.1 }}
      className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 group card-hover"
    >
      {/* Project Image */}
      <div className="relative overflow-hidden h-48 sm:h-56">
        <img
          src={project.image}
          alt={project.title}
          className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
          loading="lazy"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
      </div>

      {/* Project Info */}
      <div className="p-4 sm:p-6">
        {/* Category Badge */}
        <span className="inline-block px-3 py-1 bg-blue-100 text-blue-600 text-xs font-semibold rounded-full mb-3">
          {project.category}
        </span>

        {/* Title */}
        <h3 className="text-xl font-bold text-gray-800 mb-2 group-hover:text-blue-600 transition-colors">
          {project.title}
        </h3>

        {/* Description */}
        <p className="text-gray-600 text-sm mb-4 line-clamp-2">
          {project.description}
        </p>

        {/* Technologies */}
        <div className="flex flex-wrap gap-2 mb-4">
          {project.technologies.map((tech, idx) => (
            <span
              key={idx}
              className="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded"
            >
              {tech}
            </span>
          ))}
        </div>

        {/* View Project Button */}
        <a
          href={project.link}
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex items-center space-x-2 text-blue-600 font-semibold hover:text-blue-700 transition-colors group/btn min-h-[44px] py-2"
        >
          <span className="text-sm sm:text-base">View Project</span>
          <FaExternalLinkAlt className="w-4 h-4 group-hover/btn:translate-x-1 transition-transform" />
        </a>
      </div>
    </motion.div>
  );
};

export default PortfolioCard;
