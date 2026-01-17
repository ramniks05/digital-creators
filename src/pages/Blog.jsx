import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Calendar, User, ArrowRight, Tag } from 'lucide-react'
import { blogPosts } from '../data/mockData'
import './Blog.css'

const Blog = () => {
  const categories = ['All', ...new Set(blogPosts.map(post => post.category))]
  const [selectedCategory, setSelectedCategory] = useState('All')

  const filteredPosts = selectedCategory === 'All'
    ? blogPosts
    : blogPosts.filter(post => post.category === selectedCategory)

  return (
    <main>
      <section className="blog-hero">
        <div className="container">
          <div className="blog-hero-content">
            <h1 className="page-title">Our Blog</h1>
            <p className="page-subtitle">
              Insights, trends, and updates from the world of technology and digital solutions
            </p>
          </div>
        </div>
      </section>

      <section className="section blog-section">
        <div className="container">
          <div className="blog-filters">
            {categories.map(category => (
              <button
                key={category}
                className={`blog-filter-btn ${selectedCategory === category ? 'active' : ''}`}
                onClick={() => setSelectedCategory(category)}
              >
                {category}
              </button>
            ))}
          </div>

          <div className="blog-grid">
            {filteredPosts.map(post => (
              <article key={post.id} className="blog-card">
                <div className="blog-image-wrapper">
                  <img 
                    src={post.image} 
                    alt={post.title}
                    className="blog-image"
                  />
                  <span className="blog-category">{post.category}</span>
                </div>
                <div className="blog-content">
                  <div className="blog-meta">
                    <div className="blog-meta-item">
                      <Calendar size={16} />
                      <span>{new Date(post.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                    </div>
                    <div className="blog-meta-item">
                      <User size={16} />
                      <span>{post.author}</span>
                    </div>
                  </div>
                  <h2 className="blog-title">{post.title}</h2>
                  <p className="blog-excerpt">{post.excerpt}</p>
                  <div className="blog-tags">
                    {post.tags.map((tag, index) => (
                      <span key={index} className="blog-tag">
                        <Tag size={12} />
                        {tag}
                      </span>
                    ))}
                  </div>
                  <Link to={`/blog/${post.id}`} className="blog-link">
                    Read More <ArrowRight size={16} />
                  </Link>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>
    </main>
  )
}

export default Blog

