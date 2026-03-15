FROM nginx:alpine

# Copy static files
COPY --chown=nginx:nginx . /usr/share/nginx/html

# Install secure nginx config (overwrites default)
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Remove any config/sensitive files accidentally copied into html root
RUN rm -f /usr/share/nginx/html/nginx.conf \
    /usr/share/nginx/html/Dockerfile \
    /usr/share/nginx/html/.env

EXPOSE 80

