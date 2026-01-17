import { useParams, Link } from 'react-router-dom'
import { Calendar, User, ArrowLeft, Tag } from 'lucide-react'
import { blogPosts } from '../data/mockData'
import './BlogDetail.css'

const BlogDetail = () => {
  const { id } = useParams()
  const post = blogPosts.find(p => p.id === parseInt(id))

  if (!post) {
    return (
      <main>
        <div className="container">
          <div className="blog-not-found">
            <h2>Blog post not found</h2>
            <Link to="/blog" className="btn btn-primary">Back to Blog</Link>
          </div>
        </div>
      </main>
    )
  }

  return (
    <main>
      <article className="blog-detail">
        <div className="blog-detail-hero">
          <div className="container">
            <Link to="/blog" className="blog-back-link">
              <ArrowLeft size={18} />
              Back to Blog
            </Link>
            <div className="blog-detail-header">
              <span className="blog-detail-category">{post.category}</span>
              <h1 className="blog-detail-title">{post.title}</h1>
              <div className="blog-detail-meta">
                <div className="blog-detail-meta-item">
                  <User size={18} />
                  <span>{post.author}</span>
                </div>
                <div className="blog-detail-meta-item">
                  <Calendar size={18} />
                  <span>{new Date(post.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="blog-detail-image-wrapper">
          <img src={post.image} alt={post.title} className="blog-detail-image" />
        </div>

        <div className="container">
          <div className="blog-detail-content">
            <div className="blog-detail-body">
              <p className="blog-detail-text">{post.content}</p>
            </div>

            <div className="blog-detail-tags">
              <h3>Tags:</h3>
              <div className="blog-detail-tags-list">
                {post.tags.map((tag, index) => (
                  <span key={index} className="blog-detail-tag">
                    <Tag size={14} />
                    {tag}
                  </span>
                ))}
              </div>
            </div>

            <div className="blog-detail-author">
              <img src={post.authorImage} alt={post.author} className="blog-detail-author-image" />
              <div>
                <h4>{post.author}</h4>
                <p>Team Member at Digital Creators</p>
              </div>
            </div>
          </div>
        </div>
      </article>
    </main>
  )
}

export default BlogDetail

