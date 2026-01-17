import { motion } from 'framer-motion';
import { FaUsers, FaAward, FaProjectDiagram, FaRocket } from 'react-icons/fa';

const About = () => {
  const stats = [
    { icon: FaUsers, number: '100+', label: 'Happy Clients' },
    { icon: FaProjectDiagram, number: '200+', label: 'Projects Completed' },
    { icon: FaAward, number: '5+', label: 'Years Experience' },
    { icon: FaRocket, number: '50+', label: 'Team Members' },
  ];

  const values = [
    {
      title: 'Innovation',
      description: 'We stay ahead of the curve by adopting the latest technologies and innovative approaches to solve complex problems.',
    },
    {
      title: 'Quality',
      description: 'We are committed to delivering high-quality solutions that meet and exceed our clients\' expectations.',
    },
    {
      title: 'Integrity',
      description: 'We build trust through transparency, honesty, and ethical business practices in all our interactions.',
    },
    {
      title: 'Client Focus',
      description: 'Your success is our success. We prioritize understanding your needs and delivering solutions that drive results.',
    },
  ];

  return (
    <div>
      {/* Hero Section */}
      <section className="pt-32 pb-20 bg-gradient-to-br from-blue-50 via-purple-50 to-blue-50">
        <div className="container-custom">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8 }}
            className="text-center max-w-3xl mx-auto"
          >
            <h1 className="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
              About <span className="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Digital Creatorss</span>
            </h1>
            <p className="text-lg text-gray-600 leading-relaxed">
              We are a team of passionate developers, designers, and digital marketers dedicated to helping businesses succeed in the digital world.
            </p>
          </motion.div>
        </div>
      </section>

      {/* Company Introduction */}
      <section className="section-padding bg-white">
        <div className="container-custom">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <motion.div
              initial={{ opacity: 0, x: -50 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.8 }}
            >
              <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
                Who We Are
              </h2>
              <p className="text-gray-600 mb-4 leading-relaxed">
                Digital Creatorss is an India-based software development and digital marketing company providing custom software, website development, and online marketing solutions.
              </p>
              <p className="text-gray-600 mb-4 leading-relaxed">
                Founded with a vision to bridge the gap between businesses and technology, we have been helping companies of all sizes transform their digital presence and achieve their business goals.
              </p>
              <p className="text-gray-600 leading-relaxed">
                Our team combines technical expertise with creative thinking to deliver solutions that are not only functional but also user-friendly and visually appealing.
              </p>
            </motion.div>

            <motion.div
              initial={{ opacity: 0, x: 50 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.8 }}
              className="relative"
            >
              <img
                src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&h=600&fit=crop"
                alt="Our Team"
                className="rounded-lg shadow-xl"
              />
            </motion.div>
          </div>
        </div>
      </section>

      {/* Stats */}
      <section className="section-padding bg-gradient-to-br from-blue-600 to-purple-600 text-white">
        <div className="container-custom">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
            {stats.map((stat, index) => {
              const Icon = stat.icon;
              return (
                <motion.div
                  key={index}
                  initial={{ opacity: 0, y: 20 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.5, delay: index * 0.1 }}
                  className="text-center"
                >
                  <div className="flex justify-center mb-4">
                    <Icon className="w-12 h-12" />
                  </div>
                  <div className="text-4xl md:text-5xl font-bold mb-2">
                    {stat.number}
                  </div>
                  <div className="text-lg opacity-90">
                    {stat.label}
                  </div>
                </motion.div>
              );
            })}
          </div>
        </div>
      </section>

      {/* Mission & Vision */}
      <section className="section-padding bg-white">
        <div className="container-custom">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-12">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.6 }}
              className="bg-gradient-to-br from-blue-50 to-purple-50 p-8 rounded-lg"
            >
              <h3 className="text-2xl font-bold text-gray-900 mb-4">
                Our Mission
              </h3>
              <p className="text-gray-700 leading-relaxed">
                To empower businesses with cutting-edge digital solutions that drive growth, enhance productivity, and create lasting value. We strive to be a trusted partner in our clients' digital transformation journey.
              </p>
            </motion.div>

            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.6, delay: 0.2 }}
              className="bg-gradient-to-br from-purple-50 to-blue-50 p-8 rounded-lg"
            >
              <h3 className="text-2xl font-bold text-gray-900 mb-4">
                Our Vision
              </h3>
              <p className="text-gray-700 leading-relaxed">
                To become a leading digital solutions provider recognized for innovation, quality, and client success. We envision a future where every business can leverage technology to achieve extraordinary results.
              </p>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Why Digital Creatorss */}
      <section className="section-padding bg-gray-50">
        <div className="container-custom">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
            className="text-center mb-12"
          >
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Why Digital Creatorss?
            </h2>
            <p className="text-gray-600 max-w-2xl mx-auto">
              What sets us apart in the digital solutions industry
            </p>
          </motion.div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            {values.map((value, index) => (
              <motion.div
                key={index}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: index * 0.1 }}
                className="bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow duration-300"
              >
                <h3 className="text-xl font-bold text-gray-900 mb-3">
                  {value.title}
                </h3>
                <p className="text-gray-600 text-sm leading-relaxed">
                  {value.description}
                </p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Experience & Skills */}
      <section className="section-padding bg-white">
        <div className="container-custom">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <motion.div
              initial={{ opacity: 0, x: -50 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.8 }}
            >
              <img
                src="https://images.unsplash.com/photo-1552664730-d307ca884978?w=800&h=600&fit=crop"
                alt="Experience"
                className="rounded-lg shadow-xl"
              />
            </motion.div>

            <motion.div
              initial={{ opacity: 0, x: 50 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.8 }}
            >
              <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
                Experience & Expertise
              </h2>
              <p className="text-gray-600 mb-6 leading-relaxed">
                With years of experience in the industry, we have developed expertise across various technologies and domains. Our team is well-versed in:
              </p>
              <div className="grid grid-cols-2 gap-4">
                {[
                  'React & Next.js',
                  'Node.js & Python',
                  'WordPress & CMS',
                  'E-commerce Solutions',
                  'SEO & Analytics',
                  'Cloud Services',
                  'Mobile Development',
                  'UI/UX Design'
                ].map((skill, index) => (
                  <div key={index} className="flex items-center space-x-2">
                    <span className="w-2 h-2 bg-blue-600 rounded-full"></span>
                    <span className="text-gray-700">{skill}</span>
                  </div>
                ))}
              </div>
            </motion.div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default About;
