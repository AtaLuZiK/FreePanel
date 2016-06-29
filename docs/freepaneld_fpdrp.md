# Remote Protocol              {#fpdrp}
The FPPacket serves as an alternate transport for freepaneld's remote protocol.
Use this protocol to attach to one or more freepaneld daemon to access
remote machine to instrument network, run or stop service, etc.

## Commands

### NONE
This command generally sent response by server. If client sent, server will ignore this packet.

### DISCONNECT
If set this command, need not content, and server disconnect directly without response.

### TEST
If set this command, need not content, and server response a FPPacket and content is "success".

### VHOST
Create/Update/Delete a virtual host, accept a json content.
<table>
  <tr>
    <th>Type</th>
    <th>Segment</th>
    <th>Size (bytes)</th>
    <th>Details</th>
  <tr>
    <td rowspan="2">Request</td>
    <td>Operation Code</td>
    <td>1</td>
    <td>
      This field specified action the for virtual host.
      Difference operation code has difference configures. <br>
      This parameter must be one of the following values:
      <table>
        <tr>
          <th>Operation Code</th>
          <th>Description</th>
          <th>Configures</th>
        </tr>
        <tr>
          <td>0</td>
          <td>
            Creates a new virtual host, only if it does not already exist. <br>
            If the specified virtual host exists, the operation fails and the
            response content code is set to the reasons for the failure. <br>
            If the specified virtual host does not exist, a new virtual host
            is created. <br>
          </td>
          <td>
            <ul>
              <li>DOMAIN</li>
              <li>DOCUMENT_ROOT (optional)</li>
            </ul>
          </td>
        </tr>
        <tr>
          <td>1</td>
          <td>Updates configure for a exists virtual host</td>
          <td>
            <ul>
              <li>DOMAIN</li>
            </ul>
          </td>
        </tr>
        <tr>
          <td>2</td>
          <td>Deletes an exists virtual host</td>
          <td>
            <ul>
              <li>DOMAIN</li>
            </ul>
          </td>
        </tr>
        <tr>
          <td>3</td>
          <td>Query one or more exists virtual host</td>
          <td>
            <ul>
              <li>DOMAIN (optional)</li>
            </ul>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td>Configures</td>
    <td>*</td>
    <td></td>
  </tr>
  <tr>
    <td>Response</td>
    <td>Result</td>
    <td>*</td>
    <td>
      If the operation succeeds, the response value is "success". <br>
      If the operation fails, the return value is the reasons for the failure. <br>
    </td>
  </tr>
</table>
#### example
Creates a virtual host which domain is mydomain.test:
<table>
  <tr>
    <th>Segment</th>
    <th>Content</th>
  </tr>
  <tr>
    <th colspan="2">Request</th>
  </tr>
  <tr>
    <td>packet header</td>
    <td>refer FPPacket::Header</td>
  </tr>
  <tr>
    <td>Operatation Code</td>
    <td>0</td>
  </tr>
  <tr>
    <td>Configures</td>
    <td>{"DOMAIN":"mydomain.text"}</td>
  </tr>
  <tr>
    <td>packet footer</td>
    <td>refer FPPacket::Footer</td>
  </tr>
  <tr>
    <th colspan="2">Response</th>
  </tr>
  <tr>
    <td>packet header</td>
    <td>refer FPPacket::Header</td>
  </tr>
  <tr>
    <td>Result</td>
    <td>success</td>
  </tr>
  <tr>
    <td>packet footer</td>
    <td>refer FPPacket::Footer</td>
  </tr>
</table>

