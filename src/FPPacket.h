#ifndef FPPACKET_H_
#define FPPACKET_H_
#include <stdint.h>

typedef unsigned char byte;

#define STREAM_PTR(x) ((std::string *)x)

/**
 * a generally FreePanelPacket likely following:
 *
 * segment | note
 * --------|--------------
 * header  | refer Header
 * entity  | The entity of packet, it can be empty
 * footer  | refer Footer
 */
class FPPacket
{
public:
#pragma pack(push)
#pragma pack(1)
    /**
     * The header of FPPacket
     *
     * A header memory map likely following:
     *   sign   | version |  order  | command | reserved1 |  reserved2  | reserved3 | entitySize
     * ---------|---------|---------|---------|-----------|-------------|-----------|------------
     *  2 bytes | 2 bytes | 1 byte  | 1 byte  | 2 bytes   | 8 byte      | 8 bytes   | 8 bytes
     */
    typedef struct
    {
        uint16_t sign;      //!< must be 0x5046
        uint16_t version;   //!< default set as FREEPANELD_VERSION
        uint8_t order;      //!< setted by client, server response same order and client use this filed check response is correct
        uint8_t command;    //!< refer Command
        uint16_t reserved1;
        uint64_t reserved2;
        uint64_t reserved3;
        uint64_t entitySize;    //!< size of entity, in bytes, excluded header and footer
    } Header;

    typedef struct
    {
        uint32_t reserved;
    } Footer;
#pragma pack(pop)

    /**
     * For more info see page @ref fpdrp
     */
    enum class Command
    {
        NONE,       //!< Unset command, generally sent response by server.
        DISCONNECT, //!< If set this command, need not content, and server disconnect
                    //!< directly without response
        TEST,       //!< If set this command, need not content, and server response
                    //!< a FPPacket and content is "success".
        VHOST,      //!< Create/Update/Delete a virtual host, accept a json content
                    //!< The first byte in content specified the action as following:
                    //!< 0: Create
                    //!< 1: Update
                    //!< 2: Delete
                    //!< 3: Query
    };

    static const uint16_t SIGN;
    static const uint16_t MIN_SUPPORTED_VERSION;

    FPPacket();

    /**
     * Construct a FPPacket object, this constructor will copy memory from pHeader,
     * pFooter and entity.
     * @param pHeader A pointer to a Header struct
     * @param pFooter A pointer to a Footer struct
     * @param entity the size of entity must specified in pHeader
     */
    FPPacket(Header *pHeader, Footer *pFooter, byte *entity = nullptr);
    virtual ~FPPacket();

    /**
     * append data to entity
     * @param data
     * @param sizeInBytes
     */
    void Append(const char *data, int sizeInBytes = -1);
    void AppendUInt8(uint8_t n);

    /**
     * empty entity, but not reset header
     */
    void Empty();
    const unsigned char *Generate();

    const char *GetEntity() const;
    unsigned int GetEntitySize() const;
    uint8_t GetOrder() const;
    void SetOrder(uint8_t order);
    Command GetCommand() const;
    void SetCommand(Command cmd);


    /**
     * get the size of whole packet, in bytes.
     * @return
     */
    unsigned int GetSize() const;

    static bool CheckHeader(Header *pHeader);

    Header m_Header;
    Footer m_Footer;
    void *m_Entity;
    void *m_Data;

};

#endif /* FPPACKET_H_ */
