
rspamd_config:register_symbol({
  name = 'OPEN_TRACK',
  type = 'prefilter',
  callback = function(task)
    local cjson = require "cjson"
    local lua_mime = require "lua_mime"
    local lua_util = require "lua_util"
    local rspamd_logger = require "rspamd_logger"
    local rspamd_http = require "rspamd_http"
    local envfrom = task:get_from(1)
    local uname = task:get_user()
    if not envfrom or not uname then
      return false
    end
    local uname = uname:lower()
    local env_from_domain = envfrom[1].domain:lower()
    local env_from_addr = envfrom[1].addr:lower()

    -- determine newline type
    local function newline(task)
      local t = task:get_newlines_type()
    
      if t == 'cr' then
        return '\r'
      elseif t == 'lf' then
        return '\n'
      end
    
      return '\r\n'
    end
    -- retrieve trackable
    local function track_cb(err_message, code, data, headers)
      if err or type(data) ~= 'string' then
        rspamd_logger.infox(rspamd_config, "trackable request for user %s returned invalid or empty data (\"%s\") or error (\"%s\")", uname, data, err)
      else
        
        -- parse json string
        local trackable = cjson.decode(data)
        if not trackable then
          rspamd_logger.infox(rspamd_config, "parsing trackable for user %s returned invalid or empty data (\"%s\") or error (\"%s\")", uname, data, err)
        else
          if trackable and type(trackable) == "table" and trackable.result  then            

            if trackable.result == false then
              rspamd_logger.infox(rspamd_config, "found trackable for user %s: false", uname)
              return false
            end

            rspamd_logger.infox(rspamd_config, "found trackable for user %s: true", uname)

            local queue_id = task:get_queue_id()
            local message_id = task:get_message_id()

            if not queue_id or not message_id then
              rspamd_logger.infox(rspamd_config, "queue_id or message_id are missing")
              return false
            end

            local function bin2hex(bin)
              return (bin:gsub('.', function(byte)
                  return string.format('%02X', string.byte(byte))
              end))
            end

            local msg_hash = bin2hex(queue_id .. "|" .. message_id)
            local tracker_url = os.getenv('TRACKER_URL')
            local tracker_notice = os.getenv('TRACKER_NOTICE')

            if not tracker_url or not tracker_notice then
              rspamd_logger.infox(rspamd_config, "TRACKER_URL and TRACKER_NOTICE environment variables are required for open tracking")
              return false
            end

            local tracking_img
            local tracking_text

            if tracker_notice == "1" then
              tracking_img = string.format('<small>Opening this email with images revealed sends an anonymous notification</small><img src="%s/track.php?msg=%s" width="1" height="1" alt="tracking pixel" />', tracker_url, msg_hash)
              tracking_text = string.format('Visit %s/track.php?msg=%s to anonymously notify the sender you have opened this message.', tracker_url, msg_hash)
            else
              tracking_img = string.format('<img src="%s/track.php?msg=%s" width="1" height="1" alt="tracking pixel" />', tracker_url, msg_hash)
              tracking_text = string.format('Visit %s/track.php?msg=%s to anonymously notify the sender you have opened this message.', tracker_url, msg_hash)
            end

            -- add tracking image
            local out = {}
            local rewrite = lua_mime.add_text_footer(task, tracking_img, tracking_text) or {}
        
            local seen_cte
            local newline_s = newline(task)
        
            local function rewrite_ct_cb(name, hdr)
              if rewrite.need_rewrite_ct then
                if name:lower() == 'content-type' then
                  local nct = string.format('%s: %s/%s; charset=utf-8',
                      'Content-Type', rewrite.new_ct.type, rewrite.new_ct.subtype)
                  out[#out + 1] = nct
                  -- update Content-Type header
                  task:set_milter_reply({
                    remove_headers = {['Content-Type'] = 0},
                  })
                  task:set_milter_reply({
                    add_headers = {['Content-Type'] = string.format('%s/%s; charset=utf-8', rewrite.new_ct.type, rewrite.new_ct.subtype)}
                  })
                  return
                elseif name:lower() == 'content-transfer-encoding' then
                  out[#out + 1] = string.format('%s: %s',
                      'Content-Transfer-Encoding', 'quoted-printable')
                  -- update Content-Transfer-Encoding header
                  task:set_milter_reply({
                    remove_headers = {['Content-Transfer-Encoding'] = 0},
                  })
                  task:set_milter_reply({
                    add_headers = {['Content-Transfer-Encoding'] = 'quoted-printable'}
                  })
                  seen_cte = true
                  return
                end
              end
              out[#out + 1] = hdr.raw:gsub('\r?\n?$', '')
            end
        
            task:headers_foreach(rewrite_ct_cb, {full = true})
        
            if not seen_cte and rewrite.need_rewrite_ct then
              out[#out + 1] = string.format('%s: %s', 'Content-Transfer-Encoding', 'quoted-printable')
            end
        
            -- End of headers
            out[#out + 1] = newline_s
        
            if rewrite.out then
              for _,o in ipairs(rewrite.out) do
                out[#out + 1] = o
              end
            else
              out[#out + 1] = task:get_rawbody()
            end
            local out_parts = {}
            for _,o in ipairs(out) do
              if type(o) ~= 'table' then
                out_parts[#out_parts + 1] = o
                out_parts[#out_parts + 1] = newline_s
              else
                local removePrefix = "--\x0D\x0AContent-Type"
                if string.lower(string.sub(tostring(o[1]), 1, string.len(removePrefix))) == string.lower(removePrefix) then
                  o[1] = string.sub(tostring(o[1]), string.len("--\x0D\x0A") + 1)
                end
                out_parts[#out_parts + 1] = o[1]
                if o[2] then
                  out_parts[#out_parts + 1] = newline_s
                end
              end
            end
            task:set_message(out_parts)
          else
            rspamd_logger.infox(rspamd_config, "trackable request for user %s returned invalid or empty data (\"%s\")", uname, data)
          end
        end
      end
    end

    -- fetch trackable
    rspamd_http.request({
      task=task,
      url='http://nginx:8081/trackable.php',
      body='',
      callback=track_cb,
      headers={Domain=env_from_domain,Username=uname,From=env_from_addr},
    })

    return true
  end,
  priority = 2
})
